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

        $esSubgerente = strtolower($user->rol) === 'subgerente';

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
        if (!auth()->check() || strtolower(auth()->user()->rol) !== 'subgerente') {
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

    /**
     * Rechaza el resultado reportado y devuelve el encuentro a EN_CURSO.
     * [PATCH /v1/encuentros/{id}/rechazar]
     *
     * Solo puede rechazar el subgerente.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechazar($id)
    {
        // 1. Autorización: sólo el Subgerente
        if (!auth()->check() || strtolower(auth()->user()->rol) !== 'subgerente') {
            return response()->json([
                'message' => 'No tienes permisos para rechazar resultados de encuentros.'
            ], 403);
        }

        $encuentro = EncuentrosTorneo::findOrFail($id);

        // 2. Validación de estado: Debe estar pendiente de validación
        if ($encuentro->estatus_encuentro !== 'RESULTADO_PENDIENTE_VALIDACION') {
            return response()->json([
                'message' => 'El encuentro no tiene un resultado pendiente de validación.'
            ], 422);
        }

        // 3. Revertir marcadores y cambiar estatus
        $encuentro->update([
            'resultado_comp1' => null,
            'resultado_comp2' => null,
            'estatus_encuentro' => 'EN_CURSO'
        ]);

        return response()->json([
            'estatus_encuentro' => 'EN_CURSO',
            'message' => 'Resultado rechazado. El encuentro ha vuelto a estar En Curso.'
        ], 200);
    }

    public function misEncuentros()
    {
        if (!auth()->check() || strtolower(auth()->user()->rol) !== 'instructor') {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }
        $encuentros = EncuentrosTorneo::where('id_arbitro_asignado', auth()->user()->id)
            ->whereDate('fecha_hora_inicio', today())
            ->with([
                'torneo:id_torneo,nombre_torneo',
                'competidor1.participante',
                'competidor1.equipo',
                'competidor1.capitanDeEquipo',
                'competidor2.participante',
                'competidor2.equipo',
                'competidor2.capitanDeEquipo',
                'espacioFisico'
            ])
            ->get();

        \Log::info('DEBUG misEncuentros cargados:', $encuentros->toArray());

        return response()->json([
            'data' => $encuentros
        ]);
    }

    public function resultadosPendientes(Request $request)
    {
        if (!auth()->check() || strtolower(auth()->user()->rol) !== 'subgerente') {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $query = EncuentrosTorneo::where(
            'estatus_encuentro',
            'RESULTADO_PENDIENTE_VALIDACION'
        )
            ->whereHas('torneo', function ($q) {
                $q->whereIn('estatus_torneo', [
                    'EN_CURSO',
                    'PROGRAMADO'
                ]);
            });

        if ($request->has('torneo_id')) {
            $query->where('id_torneo', $request->input('torneo_id'));
        }

        $encuentros = $query->with([
            'torneo:id_torneo,nombre_torneo',
            'competidor1.participante',
            'competidor1.equipo',
            'competidor1.capitanDeEquipo',
            'competidor2.participante',
            'competidor2.equipo',
            'competidor2.capitanDeEquipo',
            'espacioFisico'
        ])
            ->get();

        return response()->json([
            'data' => $encuentros
        ]);
    }
}
