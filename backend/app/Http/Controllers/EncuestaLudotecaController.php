<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistorialLudoteca;

class EncuestaLudotecaController extends Controller
{
    public function obtenerEncuesta($idRegistro)
    {
        $historial = HistorialLudoteca::where(
            'id_historial',
            $idRegistro
        )->first();

        if (!$historial) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        // evitar que contesten dos veces
        if ($historial->calificacion_servicio !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Esta encuesta ya fue respondida'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id_historial' => $idRegistro,
                'mensaje' => 'Puedes responder la encuesta'
            ]
        ]);
    }


    public function guardarEncuesta(Request $request, $idRegistro)
    {
        $request->validate([
            'calificacion_servicio' => 'required|integer|min:1|max:5',
            'comentarios_padre' => 'nullable|string|max:500'
        ]);

        $historial = HistorialLudoteca::where(
            'id_historial',
            $idRegistro
        )->first();

        if (!$historial) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }


        // evitar duplicados
        if ($historial->calificacion_servicio !== null) {
            return response()->json([
                'success' => false,
                'message' => 'La encuesta ya fue contestada'
            ], 409);
        }

        $historial->update([
            'calificacion_servicio' => $request->calificacion_servicio,
            'comentarios_padre' => $request->comentarios_padre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Encuesta enviada correctamente'
        ]);
    }
}