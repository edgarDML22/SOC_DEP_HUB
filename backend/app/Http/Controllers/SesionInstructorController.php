<?php

namespace App\Http\Controllers;

use App\Models\EncuentrosTorneo;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Illuminate\Http\JsonResponse;
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
                ->with([
                    'disciplina:id_disciplina,nombre',
                    'espacio:id_espacio,nombre_espacio',
                ])
                ->orderBy('hora_inicio')
                ->get()
                ->map(fn($s) => [
                    'id_sesion'            => $s->id_sesion,
                    'disciplina'           => $s->disciplina?->nombre ?? '—',
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
                'disciplina:id_disciplina,nombre',
            ])
            ->orderBy('hora_inicio')
            ->get()
            ->map(function ($r) {
                $socio        = SocioTitular::select('id_socio', 'nombre_completo')->find($r->id_socio_titular);
                $acompanantes = $r->acompanantes_draft ?? [];

                return [
                    'id_reserva'       => $r->id_reserva,
                    'espacio'          => $r->espacioFisico?->nombre_espacio ?? '—',
                    'disciplina'       => $r->disciplina?->nombre ?? '—',
                    'hora_inicio'      => substr($r->hora_inicio ?? '', 0, 5),
                    'hora_fin'         => substr($r->hora_fin ?? '', 0, 5),
                    'socio_nombre'     => $socio?->nombre_completo ?? 'Socio',
                    'num_acompanantes' => count($acompanantes),
                    'acompanantes'     => $acompanantes,
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
                    'torneo.disciplina:id_disciplina,nombre',
                    'espacioFisico:id_espacio,nombre_espacio',
                ])
                ->orderBy('fecha_hora_inicio')
                ->get()
                ->map(fn($e) => [
                    'id_encuentro'      => $e->id_encuentro,
                    'torneo'            => $e->torneo?->nombre_torneo ?? '—',
                    'disciplina'        => $e->torneo?->disciplina?->nombre ?? '—',
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

    // -------------------------------------------------------------------------

    private function resolverNombreCompetidor(EncuentrosTorneo $e, int $num): string
    {
        $id   = $num === 1 ? $e->competidor_1_id   : $e->competidor_2_id;
        $type = $num === 1 ? $e->competidor_1_type  : $e->competidor_2_type;

        if (!$id) return 'Por definir';

        // Tipo PARTICIPANTE → socio titular
        if (str_contains(strtoupper($type ?? ''), 'SOCIO') || str_contains(strtoupper($type ?? ''), 'PARTICIPANTE')) {
            $socio = SocioTitular::select('id_socio', 'nombre_completo')->find($id);
            return $socio?->nombre_completo ?? "Competidor #{$id}";
        }

        // Tipo EQUIPO → nombre del equipo
        $equipo = \App\Models\EquipoTorneo::select('id_equipo', 'nombre_equipo')->find($id);
        return $equipo?->nombre_equipo ?? "Equipo #{$id}";
    }
}
