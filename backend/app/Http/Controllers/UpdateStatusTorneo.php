<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\torneos;
use App\Models\CategoriaTorneo;
use App\Models\Disciplina;

class UpdateStatusTorneo extends Controller
{
    public function update(Request $request)
    {
        $id_categoria = CategoriaTorneo::where('nombre_categoria', $request->nombre_categoria)->first();
        $id_disciplina = Disciplina::where('nombre_disciplina', $request->nombre_disciplina)->first();
        if ($id_categoria == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro categoria con ese nombre'
            ], 404);
        }
        if ($id_disciplina == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro disciplina con ese nombre'
            ], 404);
        }


        $torneo = torneos::where('nombre_torneo', $request->nombre_torneo)
            ->where('fecha_inicio', $request->fecha_inicio)
            ->where('id_categoria', $id_categoria->id_categoria)
            ->where('id_disciplina', $id_disciplina->id_disciplina)
            ->first();

        if (!$torneo) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro torneo con ese nombre'
            ], 404);
        }

        if ($torneo->estatus_torneo == 'PROGRAMADO') {
            return response()->json([
                'success' => false,
                'message' => 'El torneo ya se encuentra programado'
            ], 400);
        }

        $torneo->update([
            'estatus_torneo' => 'PROGRAMADO',
        ]);


        return response()->json([
            "success" => true,
            "message" => "Torneo publicado correctamente",
            'id_torneo' => $torneo->id_torneo,
            'estatus_torneo' => 'PROGRAMADO',
        ], 200);
    }
}