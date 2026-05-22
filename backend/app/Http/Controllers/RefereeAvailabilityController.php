<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\Instructor;
use App\Models\Torneo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RefereeAvailabilityController extends Controller
{
    /**
     * Obtiene TODOS los árbitros/instructores designados para un torneo
     * (sin filtro de horario).
     */
    public function all(int $id_torneo)
    {
        $torneo = Torneo::select('id_disciplina', 'pool_arbitros')
            ->where('id_torneo', $id_torneo)
            ->first();

        if (!$torneo) {
            return response()->json([
                'message' => 'Torneo no encontrado'
            ], 404);
        }

        $poolIds = $torneo->pool_arbitros ?? [];

        $query = Instructor::query()
            ->join(
                'instructor_disciplina',
                'instructores.id_instructor',
                '=',
                'instructor_disciplina.id_instructor'
            )
            ->where(
                'instructor_disciplina.id_disciplina',
                $torneo->id_disciplina
            )
            ->select(
                'instructores.id_instructor',
                'instructores.nombre_completo'
            )
            ->distinct();

        // Si el torneo tiene una pool definida, filtrar solo esos árbitros
        if (!empty($poolIds)) {
            $query->whereIn('instructores.id_instructor', $poolIds);
        }

        $instructores = $query->get();

        return response()->json([
            'arbitros' => $instructores->map(fn($i) => [
                'id_instructor' => $i->id_instructor,
                'nombre' => $i->nombre_completo,
            ])->values()
        ]);
    }

    public function available(Request $request, int $id_torneo)
    {
        $request->validate([
            'fecha_hora_inicio' => 'required|date',
            'fecha_hora_fin' => 'required|date|after:fecha_hora_inicio'
        ]);

        $torneo = Torneo::select(
            'id_disciplina',
            'pool_arbitros'
        )
            ->where('id_torneo', $id_torneo)
            ->first();

        if (!$torneo) {
            return response()->json([
                'message' => 'Torneo no encontrado'
            ], 404);
        }

        $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
        $fechaFin = Carbon::parse($request->fecha_hora_fin);

        $horaInicio = $fechaInicio->format('H:i');
        $horaFin = $fechaFin->format('H:i');

        $diasMap = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIERCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SABADO'];
        $diaSemana = $diasMap[$fechaInicio->dayOfWeek];

        $poolIds = $torneo->pool_arbitros ?? [];

        $query = Instructor::query()
            ->join(
                'instructor_disciplina',
                'instructores.id_instructor',
                '=',
                'instructor_disciplina.id_instructor'
            )
            ->where(
                'instructor_disciplina.id_disciplina',
                $torneo->id_disciplina
            )
            ->select(
                'instructores.id_instructor',
                'instructores.nombre_completo'
            )
            ->distinct();

        // Si el torneo tiene una pool definida, filtrar solo esos árbitros
        if (!empty($poolIds)) {
            $query->whereIn('instructores.id_instructor', $poolIds);
        }

        $instructores = $query->get();

        $disponibles = [];
        $ocupados = [];

        foreach ($instructores as $instructor) {

            $actividadConflicto = ActividadPlantilla::query()
                ->where(
                    'id_instructor',
                    $instructor->id_instructor
                )
                ->where(
                    'dia_semana',
                    $diaSemana
                )
                ->whereTime(
                    'hora_inicio',
                    '<',
                    $horaFin
                )
                ->whereTime(
                    'hora_fin',
                    '>',
                    $horaInicio
                )
                ->first();

            $encuentroConflicto = EncuentrosTorneo::query()
                ->where(
                    'id_arbitro_asignado',
                    $instructor->id_instructor
                )
                ->where(
                    'fecha_hora_inicio',
                    '<',
                    $request->fecha_hora_fin
                )
                ->where(
                    'fecha_hora_fin',
                    '>',
                    $request->fecha_hora_inicio
                )
                ->whereNotIn('estatus_encuentro', [
                    'BYE',
                    'FINALIZADO'
                ])
                ->first();

            $ocupado = !is_null($actividadConflicto) || !is_null($encuentroConflicto);

            $motivo = null;
            if ($actividadConflicto) {
                $motivo = "Clase programada (" . $diaSemana . ")";
            } elseif ($encuentroConflicto) {
                $torneoConflicto = $encuentroConflicto->torneo;
                $nombreT = $torneoConflicto ? $torneoConflicto->nombre_torneo : "Torneo #" . $encuentroConflicto->id_torneo;
                $horaIni = Carbon::parse($encuentroConflicto->fecha_hora_inicio)->format('H:i');
                $horaF = Carbon::parse($encuentroConflicto->fecha_hora_fin)->format('H:i');
                $motivo = "Encuentro en " . $nombreT . " (" . $horaIni . " - " . $horaF . ")";
            }

            $instructorData = [
                'id_instructor' => $instructor->id_instructor,
                'nombre' => $instructor->nombre_completo,
                'ocupado' => $ocupado,
                'motivo' => $motivo
            ];

            if ($ocupado) {
                $ocupados[] = $instructorData;
            } else {
                $disponibles[] = $instructorData;
            }
        }

        return response()->json([
            'disponibles' => $disponibles,
            'ocupados' => $ocupados
        ]);
    }
}