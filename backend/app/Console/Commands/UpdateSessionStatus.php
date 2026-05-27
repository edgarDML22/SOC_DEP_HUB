<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Máquina de estados automática para sesiones de clases programadas.
 *
 * Diseño idempotente — tolera apagones del worker:
 *
 *   Las dos transiciones evalúan la LÍNEA DE TIEMPO REAL de cada sesión
 *   sin depender del estado previo (salvo para no tocar CANCELADA).
 *   Esto garantiza que una sesión que se "saltó" la ventana EN_CURSO
 *   por falta de ejecución del worker sea rescatada en la siguiente corrida.
 *
 * Orden de ejecución (importa — FINALIZADA va primero):
 *   1. DISPONIBLE | LLENA | EN_CURSO  →  FINALIZADA
 *      Condición: hora_fin + 20 min ≤ ahora  (el evento ya terminó)
 *
 *   2. DISPONIBLE | LLENA             →  EN_CURSO
 *      Condición: hora_inicio - 15 min ≤ ahora  Y  hora_fin + 20 min > ahora
 *      (el evento está sucediendo ahora mismo o arranca en menos de 15 min
 *       y aún no entró en el margen de finalización)
 *
 * Zona horaria: todas las comparaciones se realizan en America/Mexico_City
 * (Carbon en PHP) y el campo de comparación es TIME en PostgreSQL, que es
 * zona-neutral — se compara contra el valor formateado en Mexico_City desde Carbon.
 *
 * Condición de negocio compartida:
 *   JOIN a plantillas_programacion para garantizar que la sesión pertenece
 *   a una plantilla publicada y vigente para su fecha_sesion.
 */
class UpdateSessionStatus extends Command
{
    protected $signature   = 'sessions:update-status';
    protected $description = 'Máquina de estados idempotente: rescata sesiones atrapadas y aplica transiciones por línea de tiempo real.';

    public function handle(): int
    {
        $ahora = Carbon::now('America/Mexico_City');
        $hoy   = $ahora->toDateString();

        // FINALIZADA siempre va primero: evita que una sesión que ya terminó
        // sea marcada EN_CURSO momentáneamente en la misma corrida.
        $finalizadas = $this->finalizarSesiones($ahora, $hoy);
        $iniciadas   = $this->iniciarSesiones($ahora, $hoy);

        $this->line("[{$ahora->format('H:i:s')}] FINALIZADA: +{$finalizadas} | EN_CURSO: +{$iniciadas}");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Transición 1: DISPONIBLE | LLENA | EN_CURSO → FINALIZADA
    // -------------------------------------------------------------------------

    /**
     * Finaliza toda sesión cuyo margen post-hora_fin ya expiró.
     *
     * Condición de tiempo (absoluta):
     *   hora_fin + 20 min ≤ ahora   →   hora_fin ≤ ahora - 20 min
     *
     * Estados elegibles: DISPONIBLE, LLENA, EN_CURSO.
     * Incluir DISPONIBLE y LLENA es la clave de la idempotencia: rescata sesiones
     * que quedaron "atrapadas" porque el worker no corrió durante su ventana de inicio.
     * CANCELADA queda excluida siempre.
     */
    private function finalizarSesiones(Carbon $ahora, string $hoy): int
    {
        // hora_fin debe ser <= (ahora - 20 min) para que el margen de gracia haya expirado.
        $momentoCorte = $ahora->copy()->subMinutes(20);

        // Guard de medianoche: si restar 20 minutos cruza a un día anterior,
        // significa que llevamos < 20 min en este día y ninguna sesión puede haber
        // terminado aún — salimos sin tocar nada para evitar falsos positivos.
        if ($momentoCorte->toDateString() !== $hoy) {
            return 0;
        }

        $corte = $momentoCorte->format('H:i:s');

        $afectadas = DB::table('sesiones_activas as sa')
            ->join('actividades_plantilla as ap', 'ap.id_actividad_plantilla', '=', 'sa.id_actividad_plantilla')
            ->join('plantillas_programacion as pp', 'pp.id_plantilla', '=', 'ap.id_plantilla')
            ->whereNull('ap.deleted_at')
            ->where('sa.fecha_sesion', $hoy)
            // Idempotente: cualquier estado activo puede finalizar si el tiempo lo dice.
            ->whereIn('sa.estatus_sesion', ['DISPONIBLE', 'LLENO', 'EN_CURSO'])
            // La sesión terminó hace más de 20 minutos (margen de gracia expirado).
            ->where('sa.hora_fin', '<=', $corte)
            // Condición de negocio: plantilla publicada y vigente para la fecha de la sesión.
            ->where('pp.publicada', true)
            ->whereColumn('pp.fecha_inicio', '<=', 'sa.fecha_sesion')
            ->whereColumn('pp.fecha_fin',    '>=', 'sa.fecha_sesion')
            ->update(['sa.estatus_sesion' => 'FINALIZADA']);

        if ($afectadas > 0) {
            Log::info("sessions:update-status [FINALIZADA] {$ahora} — {$afectadas} sesión(es) finalizadas.", [
                'corte_hora_fin' => $corte,
                'fecha'          => $hoy,
            ]);
            $this->line("  → {$afectadas} sesión(es) marcadas FINALIZADA (hora_fin ≤ {$corte})");
        }

        return $afectadas;

        /*
         * =====================================================================
         * TODO (US-33 / US-35) — Evaluación de No-Shows automáticos
         * =====================================================================
         * En una versión futura, inmediatamente después del UPDATE anterior,
         * este método deberá evaluar y registrar los No-Shows automáticos
         * para las clases CERRADAS (requiere_inscripcion = true).
         *
         * Lógica prevista:
         *   1. Para cada sesión recién marcada FINALIZADA con requiere_inscripcion = true,
         *      obtener todas las inscripciones en estatus CONFIRMADA (no ASISTIO,
         *      no FALTA) de la tabla inscripciones_clases.
         *
         *   2. Para cada inscripción CONFIRMADA, verificar si el id_usuario
         *      aparece en la tabla registros_asistencia con id_sesion coincidente
         *      y un estatus de presencia positivo.
         *
         *   3. Si el socio NO figura en registros_asistencia → marcar la inscripción
         *      como FALTA (No-Show automático) e incrementar contador_no_shows en
         *      socios_titulares para el socio titular responsable.
         *
         *   4. Llamar a Sanciones::aplicarSancionesReservas($socioId) para aplicar
         *      las penalizaciones según las reglas de negocio vigentes.
         *
         *   5. El mismo flujo aplica para miembro_familiar: obtener el socio_id del
         *      miembro desde miembros_familiares y acumular la falta al titular.
         *
         *   6. Los invitados (id_usuario < 0) quedan excluidos de la penalización
         *      automática, igual que en la cancelación manual.
         *
         * GUARDIA CRÍTICA: No ejecutar si lista_asistencia_enviada = true — en ese
         * caso el instructor ya marcó los estatus manualmente y no deben sobreescribirse.
         * =====================================================================
         */
    }

    // -------------------------------------------------------------------------
    // Transición 2: DISPONIBLE | LLENA → EN_CURSO
    // -------------------------------------------------------------------------

    /**
     * Activa las sesiones cuyo evento está ocurriendo ahora mismo.
     *
     * Condición de tiempo (absoluta, ventana activa):
     *   hora_inicio - 15 min ≤ ahora   →   hora_inicio ≤ ahora + 15 min  (ya arrancó o arranca pronto)
     *   hora_fin    + 20 min  > ahora   →   hora_fin    > ahora - 20 min  (el margen de gracia no expiró)
     *
     * La segunda condición garantiza que no se marque EN_CURSO una sesión que
     * finalizarSesiones() ya debería haber capturado en esta misma corrida.
     * En la práctica finalizarSesiones() corre primero, pero la doble guarda
     * hace el comando verdaderamente idempotente ante cualquier orden de ejecución.
     *
     * Estados elegibles: DISPONIBLE, LLENA únicamente.
     * EN_CURSO ya está en el estado correcto; FINALIZADA y CANCELADA se excluyen.
     */
    private function iniciarSesiones(Carbon $ahora, string $hoy): int
    {
        // La sesión arranca en ≤ 15 min desde ahora.
        $momentoVentana = $ahora->copy()->addMinutes(15);
        // Si añadir 15 min cruza a mañana, capamos la ventana a fin del día actual
        // para evitar activar sesiones del día siguiente.
        $ventanaInicio = $momentoVentana->toDateString() === $hoy
            ? $momentoVentana->format('H:i:s')
            : '23:59:59';

        // El margen post-hora_fin no ha expirado (sesión aún "viva").
        // Si restar 20 min cruza a ayer, usamos '00:00:00' para no excluir nada.
        $momentoCorteViva = $ahora->copy()->subMinutes(20);
        $corteViva        = $momentoCorteViva->toDateString() === $hoy
            ? $momentoCorteViva->format('H:i:s')
            : '00:00:00';

        $afectadas = DB::table('sesiones_activas as sa')
            ->join('actividades_plantilla as ap', 'ap.id_actividad_plantilla', '=', 'sa.id_actividad_plantilla')
            ->join('plantillas_programacion as pp', 'pp.id_plantilla', '=', 'ap.id_plantilla')
            ->whereNull('ap.deleted_at')
            ->where('sa.fecha_sesion', $hoy)
            ->whereIn('sa.estatus_sesion', ['DISPONIBLE', 'LLENO'])
            // La sesión arranca dentro de los próximos 15 min (o ya arrancó).
            ->where('sa.hora_inicio', '<=', $ventanaInicio)
            // La sesión aún no entró en el margen de finalización — está "viva".
            ->where('sa.hora_fin', '>', $corteViva)
            // Condición de negocio: plantilla publicada y vigente.
            ->where('pp.publicada', true)
            ->whereColumn('pp.fecha_inicio', '<=', 'sa.fecha_sesion')
            ->whereColumn('pp.fecha_fin',    '>=', 'sa.fecha_sesion')
            ->update(['sa.estatus_sesion' => 'EN_CURSO']);

        if ($afectadas > 0) {
            Log::info("sessions:update-status [EN_CURSO] {$ahora} — {$afectadas} sesión(es) iniciadas.", [
                'ventana_hasta' => $ventanaInicio,
                'corte_viva'    => $corteViva,
                'fecha'         => $hoy,
            ]);
            $this->line("  → {$afectadas} sesión(es) marcadas EN_CURSO (hora_inicio ≤ {$ventanaInicio} y aún activa)");
        }

        return $afectadas;
    }
}
