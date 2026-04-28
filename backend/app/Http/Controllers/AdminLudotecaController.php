<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TurnosLudoteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RegistrosLudoteca;


class AdminLudotecaController extends Controller
{
    public function store(Request $request)
    {
        $instructor = Instructor::find($request->id_instructor);

        $tieneLudoteca = $instructor->disciplinas()
            ->where('disciplinas.id_disciplina', 26)
            ->exists();
        if (!$tieneLudoteca) {
            return response()->json([
                'status' => false,
                'message' => 'Este instructor no tiene permiso para impartir clases de ludoteca',
            ], 409);
        }
        $available = InstructorAvailabilityService::validarDisponibilidad($request->id_instructor, $request->fecha, $request->hora_inicio, $request->hora_fin);
        if ($available) {
            return response()->json([
                'status' => false,
                'message' => 'Instructor no disponible',
            ], 409);
        }
        TurnosLudoteca::create([
            'id_instructor' => $request->id_instructor,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,

        ]);
        return response()->json([
            'status' => true,
            'message' => 'Turno creado exitosamente'
        ], 201);
    }

    public function getInstructores()
    {
        $instructores = DB::table('instructores as i')
            ->join('instructor_disciplina as id', 'i.id_instructor', '=', 'id.id_instructor')
            ->where('id.id_disciplina', 26)
            ->select('i.id_instructor', 'i.nombre_completo')
            ->orderBy('i.nombre_completo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $instructores
        ]);
    }

    public function getTurnos()
    {
        $turnos = DB::table('turnos_ludoteca as t')
            ->join('instructores as i', 't.id_instructor', '=', 'i.id_instructor')
            ->whereBetween('t.fecha', [
                now()->toDateString(),
                now()->addDays(6)->toDateString()
            ])
            ->orderBy('t.fecha')
            ->orderBy('t.hora_inicio')
            ->select(
                't.id_turno',
                't.fecha',
                't.hora_inicio',
                't.hora_fin',
                'i.nombre_completo as instructor',
                'i.id_instructor'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $turnos
        ]);
    }

    public function getStats()
    {

        $ocupacion = RegistrosLudoteca::where('estatus_ludoteca', 'ACTIVA')->count();

        $incidencias = RegistrosLudoteca::whereDate('hora_ingreso', today())
            ->where('estatus_ludoteca', 'COMPLETADA_CON_RETRASO')
            ->count();

        // Promedio de calificación de encuestas del día
        $calificacionPromedio = null;
        try {
            $avg = DB::table('encuestas_ludoteca')
                ->whereDate('created_at', today())
                ->avg('calificacion');

            $calificacionPromedio = $avg ? round($avg, 1) : null;
        } catch (\Exception $e) {
        }


        $totalHoy = RegistrosLudoteca::whereDate('hora_ingreso', today())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'ocupacion_actual' => $ocupacion,
                'calificacion_promedio' => $calificacionPromedio,
                'incidencias_dia' => $incidencias,
                'total_hoy' => $totalHoy,
            ]
        ]);
    }



}