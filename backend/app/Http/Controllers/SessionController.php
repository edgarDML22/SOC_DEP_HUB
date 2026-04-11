<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el ID del instructor autenticado a traves del token (user_id apunta a su ID real en la tabla)
        $user = $request->user();

        if ($user->rol !== 'instructor') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Rol inválido.'
            ], 403);
        }

        $instructorId = $user->user_id;

        // Consultamos la tabla actividades_plantilla
        $actividades = ActividadPlantilla::with(['espacioFisico', 'disciplina'])
            ->where('id_instructor', $instructorId)
            ->where('estatus', 'ACTIVO')
            ->get();

        // Mapear los datos para entregarlos como espera el frontend
        $sesiones = $actividades->map(function ($act) {
            return [
                'id' => $act->id_actividad_plantilla,
                'diaSemana' => strtoupper($act->dia_semana),
                'horaInicio' => substr($act->hora_inicio, 0, 5), // 'HH:MM'
                'horaFin' => substr($act->hora_fin, 0, 5),
                'espacio' => $act->espacioFisico ? $act->espacioFisico->nombre_espacio : 'No asignado',
                'capacidadMaxima' => $act->cupo_maximo,
                'tipo' => $act->disciplina ? $act->disciplina->nombre_disciplina : 'Clase',
                'status' => 'Programada',
                'statusType' => 'success-dark'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sesiones
        ], 200);
    }
}
