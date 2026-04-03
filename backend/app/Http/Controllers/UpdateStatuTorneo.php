<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\torneos;

class UpdateStatuTorneo extends Controller
{
    public function update(Request $request)
    {
        $torneo = torneos::find($request->id_torneo);
        if (!$torneo) {
            return response()->json([
                'success' => false,
                'message' => 'Torneo no encontrado'
            ], 404);
        }
        $validation = torneos::where('id_torneo', $request->id_torneo)->first();
        if ($validation->estatus_torneo != 'EN_PLANIFICACION') {
            return response()->json([
                'success' => false,
                'message' => 'El torneo no se encuentra en estado de planificacion'
            ], 400);
        }

        torneos::where('id_torneo', $request->id_torneo)->update([
            'estatus_torneo' => 'PROGRAMADO',
        ]);


        return response()->json([
            "success" => true,
            "message" => "Torneo publicado correctamente",
            'id_torneo' => $request->id_torneo,
            'estatus_torneo' => 'PROGRAMADO',
        ], 200);
    }
}