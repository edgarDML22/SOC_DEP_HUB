<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\SesionActiva;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\MongoDB\RegistroAsistecia;
use App\Models\EspacioFisico;
use Illuminate\Validation\ValidationException;
use App\Models\ActividadPlantilla;



class RegisterEventController extends Controller
{
    public function register_event(Request $request)
    {
        $id_user = User::where("email", $request->email)->first();
        if (!$id_user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro usuario con ese email'
            ], 404);
        }
        $id_sesion = SesionActiva::where("id_sesion", $request->id_sesion)->first();
        if (!$id_sesion) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro sesion con ese id'
            ], 404);
        }
        $id_actividad = ActividadPlantilla::where("id_actividad_plantilla", $id_sesion->id_actividad_plantilla)->first();
        if (!$id_actividad) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro actividad con ese id'
            ], 404);
        }

        try {
            $request->validate([
                'fase' => 'required|in:ingreso,cierre',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
        $data = [
            'socio_id' => $id_user->user_id,
            'id_sesion' => $id_sesion->id_sesion,
            'fase' => $request->fase,
            'timestamp' => now(),
            'metadata' => [
                'dia_semana' => $id_actividad->dia_semana ?? null,
                'estatus' => $id_actividad->estatus ?? null,

            ],
        ];


        $registro = RegistroAsistecia::create($data);

        if ($registro) {
            return response()->json([
                'success' => true,
                'message' => 'Registro de asistencia creado correctamente',
                'data' => $registro
            ], 201);
        }


        return response()->json([
            'success' => false,
            'message' => 'No se pudo crear el registro de asistencia'
        ], 500);

    }
}