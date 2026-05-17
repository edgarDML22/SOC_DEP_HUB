<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EncuentrosTorneo;
use App\Actions\Torneo\AvanzarBracketAction;

class ResultadoController extends Controller
{
    /**
     * Reporta los marcadores preliminares de un encuentro.
     * [PATCH /v1/encuentros/{id}/resultado]
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportar(Request $request, $id)
    {
        $encuentro = EncuentrosTorneo::findOrFail($id);

        // 1. Autorización: sólo el árbitro asignado al encuentro
        if (auth()->id() !== $encuentro->id_arbitro_asignado) {
            return response()->json([
                'message' => 'No eres el árbitro asignado a este encuentro.'
            ], 403);
        }

        // 2. Validación: No se puede reportar resultado de un encuentro con es_bye = true
        if ($encuentro->es_bye) {
            return response()->json([
                'message' => 'No se puede reportar el resultado de un encuentro con Bye.'
            ], 422);
        }

        // 3. Validación de estado: El encuentro debe estar en curso
        if ($encuentro->estatus_encuentro !== 'EN_CURSO') {
            return response()->json([
                'message' => 'El encuentro no está en curso.'
            ], 422);
        }

        // 4. Validar formato de los marcadores
        $request->validate([
            'resultado_comp1' => 'required|integer|min:0',
            'resultado_comp2' => 'required|integer|min:0',
        ]);

        // Los dos resultados no pueden ser iguales (debe haber ganador para avanzar)
        if ($request->resultado_comp1 == $request->resultado_comp2) {
            return response()->json([
                'message' => 'Los resultados no pueden ser iguales. Debe haber un ganador.'
            ], 422);
        }

        // 5. Guardar marcadores preliminares y cambiar estatus
        $encuentro->update([
            'resultado_comp1' => $request->resultado_comp1,
            'resultado_comp2' => $request->resultado_comp2,
            'estatus_encuentro' => 'RESULTADO_PENDIENTE_VALIDACION'
        ]);

        return response()->json([
            'estatus_encuentro' => 'RESULTADO_PENDIENTE_VALIDACION'
        ], 200);
    }

    /**
     * Valida el resultado reportado y avanza al ganador en el bracket.
     * [PATCH /v1/encuentros/{id}/validar]
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function validar($id)
    {
        // 1. Autorización: sólo el Subgerente
        if (auth()->user()->rol !== 'subgerente') {
            return response()->json([
                'message' => 'No tienes permisos para validar resultados de encuentros.'
            ], 403);
        }

        $encuentro = EncuentrosTorneo::findOrFail($id);

        // 2. Validación de estado: Debe estar pendiente de validación
        if ($encuentro->estatus_encuentro !== 'RESULTADO_PENDIENTE_VALIDACION') {
            return response()->json([
                'message' => 'El encuentro no tiene un resultado pendiente de validación.'
            ], 422);
        }

        // 3. Propagar ganador en el bracket
        $resultado = app(AvanzarBracketAction::class)->execute($encuentro);

        return response()->json([
            'estatus_encuentro' => 'FINALIZADO',
            'ganador_id' => $resultado['ganador_id'],
            'ganador_type' => $resultado['ganador_type']
        ], 200);
    }
}
