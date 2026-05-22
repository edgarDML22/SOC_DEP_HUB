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
        $torneo = Torneo::select('id_disciplina')
            ->where('id_torneo', $id_torneo)
            ->first();

        if (!$torneo) {
            return response()->json([
                'message' => 'Torneo no encontrado'
            ], 404);
        }

        $instructores = Instructor::query()
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
            ->distinct()
            ->get();

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

        $instructores = Instructor::query()
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
            ->distinct()
            ->get();

        $disponibles = [];
        $ocupados = [];

        foreach ($instructores as $instructor) {

            $conflictoActividad = ActividadPlantilla::query()
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
                ->exists();

            $conflictoEncuentro = EncuentrosTorneo::query()
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
                ->exists();

            $ocupado = $conflictoActividad || $conflictoEncuentro;

            $instructorData = [
                'id_instructor' => $instructor->id_instructor,
                'nombre' => $instructor->nombre_completo,
                'ocupado' => $ocupado
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