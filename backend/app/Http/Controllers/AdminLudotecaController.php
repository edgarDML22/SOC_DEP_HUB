<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TurnosLudoteca;
use Illuminate\Http\Request;

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



}