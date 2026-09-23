<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Torneo;

use App\Actions\Torneo\TransitionTorneoStatusAction;
use App\Exceptions\InsufficientParticipantsException;

class UpdateStatusTorneo extends Controller
{
    public function update(
        Request $request,
        $id,
        TransitionTorneoStatusAction $action
    ) {

        $request->validate([

            'nuevo_estatus' => [

                'required',

                Rule::in([
                    'EN_PLANIFICACION',
                    'EN_INSCRIPCION',
                    'PROGRAMADO',
                    'EN_CURSO',
                    'FINALIZADO',
                    'CANCELADO'
                ])
            ],

            'motivo_cancelacion' => 'nullable|string'
        ]);

        $torneo = Torneo::findOrFail($id);

        try {
            $resultado = $action->execute(
                $torneo,
                $request->nuevo_estatus,
                $request->motivo_cancelacion
            );
        } catch (InsufficientParticipantsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json($resultado);
    }
}