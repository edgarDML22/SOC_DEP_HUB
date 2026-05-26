<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\EncuentrosTorneo;
use App\Models\InscripcionClase;
use App\Models\MiembrosFamiliares;
use App\Models\RegistroAsistencia;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SesionInstructorController extends Controller
{
    /**
     * GET /api/v1/instructor/datos-hoy
     *
     * Retorna en un solo request las sesiones, reservaciones y encuentros del día.
     */
    public function datosHoy(): JsonResponse
    {
        $idInstructor = auth()->user()->instructor?->id_instructor;
        $hoy          = Carbon::today();

        // --- Sesiones --------------------------------------------------------
        $sesiones = [];
        if ($idInstructor) {
            $sesiones = SesionActiva::withoutGlobalScopes()
                ->where('id_instructor', $idInstructor)
                ->whereDate('fecha_sesion', $hoy)
                ->whereRaw("(fecha_sesion::date + hora_fin::time) > (NOW() - INTERVAL '15 minutes')")
                ->whereNotIn('estatus_sesion', ['CANCELADA', 'CANCELADA_POR_TORNEO'])
                ->with([
                    'disciplina:id_disciplina,nombre_disciplina',
                    'espacio:id_espacio,nombre_espacio',
                ])
                ->orderBy('hora_inicio')
                ->get()
                ->map(fn($s) => [
                    'id_sesion'            => $s->id_sesion,
                    'disciplina'           => $s->disciplina?->nombre_disciplina ?? '—',
                    'espacio'              => $s->espacio?->nombre_espacio ?? '—',
                    'hora_inicio'          => substr($s->hora_inicio ?? '', 0, 5),
                    'hora_fin'             => substr($s->hora_fin ?? '', 0, 5),
                    'cantidad_inscritos'   => (int) $s->cantidad_inscritos,
                    'cupo_maximo'          => (int) $s->cupo_maximo,
                    'requiere_inscripcion' => (bool) $s->requiere_inscripcion,
                    'estatus_sesion'       => $s->estatus_sesion,
                ])
                ->all();
        }

        // --- Reservaciones ---------------------------------------------------
        $reservaciones = Reservacion::whereDate('fecha_reserva', $hoy)
            ->where('estatus_operativo', 'ACTIVA')
            ->whereRaw("hora_fin::time > NOW()::time")
            ->with([
                'espacioFisico:id_espacio,nombre_espacio',
                'disciplina:id_disciplina,nombre_disciplina',
            ])
            ->orderBy('hora_inicio')
            ->get()
            ->map(function ($r) {
                $socio        = SocioTitular::select('id_socio', 'nombre_completo')->find($r->id_socio_titular);
                $acompanantes = $r->acompanantes_draft ?? [];

                return [
                    'id_reserva'        => $r->id_reserva,
                    'espacio'           => $r->espacioFisico?->nombre_espacio ?? '—',
                    'disciplina'        => $r->disciplina?->nombre_disciplina ?? '—',
                    'hora_inicio'       => substr($r->hora_inicio ?? '', 0, 5),
                    'hora_fin'          => substr($r->hora_fin ?? '', 0, 5),
                    'socio_nombre'      => $socio?->nombre_completo ?? 'Socio',
                    'num_acompanantes'  => count($acompanantes),
                    'acompanantes'      => $acompanantes,
                    'estatus_operativo' => $r->estatus_operativo,
                ];
            })
            ->all();

        // --- Encuentros ------------------------------------------------------
        $encuentros = [];
        if ($idInstructor) {
            $encuentros = EncuentrosTorneo::where('id_arbitro_asignado', $idInstructor)
                ->whereDate('fecha_hora_inicio', $hoy)
                ->whereNotIn('estatus_encuentro', ['CANCELADO'])
                ->with([
                    'torneo:id_torneo,nombre_torneo,id_disciplina',
                    'torneo.disciplina:id_disciplina,nombre_disciplina',
                    'espacioFisico:id_espacio,nombre_espacio',
                ])
                ->orderBy('fecha_hora_inicio')
                ->get()
                ->map(fn($e) => [
                    'id_encuentro'      => $e->id_encuentro,
                    'torneo'            => $e->torneo?->nombre_torneo ?? '—',
                    'disciplina'        => $e->torneo?->disciplina?->nombre_disciplina ?? '—',
                    'fase_bracket'      => $e->fase_bracket,
                    'numero_encuentro'  => $e->numero_encuentro,
                    'espacio'           => $e->espacioFisico?->nombre_espacio ?? '—',
                    'hora_inicio'       => Carbon::parse($e->fecha_hora_inicio)->format('H:i'),
                    'hora_fin'          => $e->fecha_hora_fin
                        ? Carbon::parse($e->fecha_hora_fin)->format('H:i')
                        : null,
                    'estatus_encuentro' => $e->estatus_encuentro,
                    'competidor_1'      => $this->resolverNombreCompetidor($e, 1),
                    'competidor_2'      => $this->resolverNombreCompetidor($e, 2),
                ])
                ->all();
        }

        return response()->json([
            'sesiones'      => $sesiones,
            'reservaciones' => $reservaciones,
            'encuentros'    => $encuentros,
        ]);
    }

    /**
     * GET /api/v1/instructor/sesiones/{idSesion}/lista-inscriptos
     *
     * Lista de socios inscritos en una sesión cerrada (requiere_inscripcion = true)
     * con su estado actual de asistencia. Usado por PaseLista.vue al montar el pase de lista.
     *
     * estatus_asistencia es null (JSON null) para socios sin escanear — nunca el string "PENDIENTE".
     */
    public function listaInscriptos(Request $request, int $idSesion): JsonResponse
    {
        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($idSesion);

        $idInstructor = auth()->user()->instructor?->id_instructor;
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        // Inscritos activos — 1 query con eager load
        $inscritos = InscripcionClase::where('id_sesion', $idSesion)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA'])
            ->get();

        // Registros de asistencia ya existentes — 1 query (pluck evita N+1)
        $registros = RegistroAsistencia::where('id_sesion', $idSesion)
            ->pluck('asistencia', 'id_usuario');

        // Códigos QR activos por usuario inscrito — 1 query
        $idsUsuarios = $inscritos->pluck('id_usuario')->unique()->values();
        $codigos = CodigoQr::whereIn('usuario_id', $idsUsuarios)
            ->where('estatus', 'ACTIVO')
            ->pluck('codigo', 'usuario_id');

        $resultado = $inscritos->map(function ($inscripcion) use ($registros, $codigos) {
            $nombre = $this->resolverNombreInscrito(
                $inscripcion->id_usuario,
                $inscripcion->tipo_usuario
            );

            // null si no hay registro — respeta que "PENDIENTE" no existe en el enum de PostgreSQL
            $asistencia = $registros->has($inscripcion->id_usuario)
                ? ($registros->get($inscripcion->id_usuario) ? 'PRESENTE' : 'FALTA')
                : null;

            return [
                'id_inscripcion'      => $inscripcion->id_inscripcion,
                'id_usuario'          => $inscripcion->id_usuario,
                'tipo_usuario'        => $inscripcion->tipo_usuario,
                'nombre'              => $nombre,
                'codigo_qr'           => $codigos->get($inscripcion->id_usuario),
                'estatus_asistencia'  => $asistencia,
            ];
        });

        return response()->json(['data' => $resultado->values()]);
    }

    /**
     * POST /api/v1/instructor/sesiones/{idSesion}/confirmar-asistencia
     *
     * Activa lista_asistencia_enviada = true en la sesión, lo que previene
     * que el Cron Job de 15 minutos (US-35) sancione a los socios faltistas.
     */
    public function confirmarAsistencia(Request $request, int $idSesion): JsonResponse
    {
        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($idSesion);

        $idInstructor = auth()->user()->instructor?->id_instructor;
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        if ($sesion->lista_asistencia_enviada) {
            return response()->json([
                'message' => 'Esta sesión ya fue confirmada.',
            ], 422);
        }

        $sesion->update(['lista_asistencia_enviada' => true]);

        return response()->json([
            'message' => 'Lista de asistencia confirmada.',
        ]);
    }

    // -------------------------------------------------------------------------

    private function resolverNombreInscrito(int $idUsuario, string $tipoUsuario): string
    {
        return match (strtolower($tipoUsuario)) {
            'socio_titular'     => SocioTitular::select('nombre_completo')->find($idUsuario)?->nombre_completo ?? "Socio #{$idUsuario}",
            'miembro_familiar'  => MiembrosFamiliares::select('nombre_completo')->find($idUsuario)?->nombre_completo ?? "Familiar #{$idUsuario}",
            default             => "Usuario #{$idUsuario}",
        };
    }

    private function resolverNombreCompetidor(EncuentrosTorneo $e, int $num): string
    {
        $id   = $num === 1 ? $e->competidor_1_id   : $e->competidor_2_id;
        $type = $num === 1 ? $e->competidor_1_type  : $e->competidor_2_type;

        if (!$id) return 'Por definir';

        if (str_contains(strtoupper($type ?? ''), 'SOCIO') || str_contains(strtoupper($type ?? ''), 'PARTICIPANTE')) {
            $socio = SocioTitular::select('id_socio', 'nombre_completo')->find($id);
            return $socio?->nombre_completo ?? "Competidor #{$id}";
        }

        $equipo = \App\Models\EquipoTorneo::select('id_equipo', 'nombre_equipo')->find($id);
        return $equipo?->nombre_equipo ?? "Equipo #{$id}";
    }
}
