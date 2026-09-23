<?php

namespace App\Console\Commands;

use App\Models\InscripcionClase;
use App\Models\MiembrosFamiliares;
use App\Models\RegistroAsistencia;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use App\Notifications\NoShowPenalization;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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

        // Procesar no-shows solo si hubo sesiones recién finalizadas
        if ($finalizadas > 0) {
            $this->procesarNoShows($ahora, $hoy);
        }

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
    }

    // -------------------------------------------------------------------------
    // No-Shows: sesiones CERRADAS recién finalizadas sin lista confirmada
    // -------------------------------------------------------------------------

    /**
     * Para cada sesión cerrada (requiere_inscripcion=true) recién marcada FINALIZADA,
     * convierte toda inscripción que siga en CONFIRMADA → FALTA, inserta su registro
     * de no-show en registros_asistencia e incrementa el contador del socio titular
     * responsable.
     *
     * Se procesa SIEMPRE (sin filtrar por lista_asistencia_enviada): los inscritos
     * que el instructor marcó presentes ya pasaron a ASISTIO y no entran en el filtro
     * estatus_inscripcion=CONFIRMADA, por lo que no hay riesgo de sobreescribir nada.
     * Esto garantiza que en sesiones FINALIZADAS no queden inscripciones colgando
     * en CONFIRMADA — el modal de gestión solo verá ASISTENCIA y NO-SHOW.
     */
    private function procesarNoShows(Carbon $ahora, string $hoy): void
    {
        // Sesiones cerradas recién finalizadas — se procesan SIEMPRE, sin importar
        // si el instructor envió o no la lista de asistencia. Cualquier inscripción
        // que aún esté CONFIRMADA en una sesión finalizada es, por definición, un no-show:
        // el instructor tuvo su ventana para marcarlo presente y no lo hizo.
        $sesiones = SesionActiva::withoutGlobalScopes()
            ->where('fecha_sesion', $hoy)
            ->where('estatus_sesion', 'FINALIZADA')
            ->where('requiere_inscripcion', true)
            ->get(['id_sesion']);

        if ($sesiones->isEmpty()) {
            return;
        }

        $idsSesiones = $sesiones->pluck('id_sesion');

        // IDs de usuarios que SÍ tienen registro de asistencia positivo, por sesión
        // Estructura: ['id_sesion' => Set<id_usuario>, ...]
        $presentes = RegistroAsistencia::whereIn('id_sesion', $idsSesiones)
            ->where('asistencia', true)
            ->get(['id_sesion', 'id_usuario'])
            ->groupBy('id_sesion')
            ->map(fn($rows) => $rows->pluck('id_usuario')->flip()); // flip para O(1) lookup

        // Inscripciones CONFIRMADAS en esas sesiones (no ASISTIO, no FALTA ya procesada)
        InscripcionClase::whereIn('id_sesion', $idsSesiones)
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->whereNotIn('tipo_usuario', ['invitado']) // invitados no se penalizan
            ->chunkById(200, function ($inscripciones) use ($presentes, $ahora) {
                foreach ($inscripciones as $inscripcion) {
                    $yaPresente = isset($presentes[$inscripcion->id_sesion])
                        && $presentes[$inscripcion->id_sesion]->has($inscripcion->id_usuario);

                    if ($yaPresente) {
                        continue;
                    }

                    DB::transaction(function () use ($inscripcion, $ahora) {
                        // Registrar la falta
                        RegistroAsistencia::create([
                            'id_sesion'           => $inscripcion->id_sesion,
                            'id_usuario'          => $inscripcion->id_usuario,
                            'tipo_usuario'        => $inscripcion->tipo_usuario,
                            'asistencia'          => false,
                            'metodo_registro'     => 'SIN_REGISTRO',
                            'fecha_hora_registro' => $ahora,
                        ]);

                        // Actualizar estatus de la inscripción
                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);

                        // Resolver el socio titular responsable de la penalización
                        $socioId = $this->resolverSocioResponsable(
                            $inscripcion->id_usuario,
                            $inscripcion->tipo_usuario
                        );

                        if (!$socioId) {
                            return;
                        }

                        $socio = SocioTitular::find($socioId);
                        if (!$socio) {
                            return;
                        }

                        $socio->increment('contador_no_shows');
                        $socio->refresh();

                        // Aplicar penalización al alcanzar 3 no-shows acumulados
                        if ($socio->contador_no_shows >= 3 && $socio->estatus_cuenta !== 'SUSPENDIDO') {
                            $socio->update([
                                'estatus_cuenta'              => 'SUSPENDIDO',
                                'fecha_fin_penalizacion_reserva' => now('America/Mexico_City')
                                                                        ->addDays(7)
                                                                        ->startOfDay(),
                            ]);
                        }

                        // Notificación de no-show (falla silenciosa para no romper el cron)
                        if ($socio->correo_electronico) {
                            try {
                                Notification::route('mail', $socio->correo_electronico)
                                    ->notify(new NoShowPenalization('de la clase'));
                            } catch (\Throwable $e) {
                                Log::warning("sessions:update-status [NO-SHOW] Fallo notificación socio #{$socioId}: {$e->getMessage()}");
                            }
                        }
                    });
                }
            }, 'id_inscripcion');

        $this->line("  → No-shows procesados para sesiones en {$hoy}");
    }

    /**
     * Para miembros_familiares, la penalización recae sobre su socio titular.
     * Para socios_titulares, se penaliza directamente.
     */
    private function resolverSocioResponsable(int $idUsuario, string $tipoUsuario): ?int
    {
        return match (strtolower($tipoUsuario)) {
            'socio_titular'    => $idUsuario,
            'miembro_familiar' => MiembrosFamiliares::select('socio_id')
                                    ->find($idUsuario)
                                    ?->socio_id,
            default            => null,
        };
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
