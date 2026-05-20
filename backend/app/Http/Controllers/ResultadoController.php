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
     * Pueden reportar: el árbitro asignado al encuentro O un subgerente.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportar(Request $request, $id)
    {
        $encuentro = EncuentrosTorneo::findOrFail($id);

        $user = auth()->user();

        // 1. Autorización: árbitro asignado O subgerente
        $esArbitroAsignado = $encuentro->id_arbitro_asignado !== null
            && $user->id === (int) $encuentro->id_arbitro_asignado;

        $esSubgerente = $user->rol === 'subgerente';

        if (!$esArbitroAsignado && !$esSubgerente) {
            return response()->json([
                'message' => 'No tienes permiso para reportar el resultado de este encuentro. Solo el árbitro asignado o un subgerente pueden hacerlo.'
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
     * Solo puede validar el subgerente.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function validar($id)
    {
        // 1. Autorización: sólo el Subgerente
        if (!auth()->check() || auth()->user()->rol !== 'subgerente') {
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
            'estatus_encuentro'  => 'FINALIZADO',
            'ganador_id'         => $resultado['ganador_id'],
            'ganador_type'       => $resultado['ganador_type'],
            'id_encuentro'       => $encuentro->id_encuentro,
            'id_torneo'          => $encuentro->id_torneo,
            'fase_bracket'       => $encuentro->fase_bracket,
            'torneo_finalizado'  => $resultado['torneo_finalizado'],
        ], 200);
    }
}
