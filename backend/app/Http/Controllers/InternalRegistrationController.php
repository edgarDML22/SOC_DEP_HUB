<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\ParticipantesTorneo;
use App\Actions\Torneo\ValidateEligibilityAction;
use App\Exceptions\EligibilityException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class InternalRegistrationController extends Controller
{
    /**
     * Registra un participante interno directamente en el torneo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id (id_torneo)
     * @param  \App\Actions\Torneo\ValidateEligibilityAction  $validateEligibility
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $id, ValidateEligibilityAction $validateEligibility)
    {
        // 1. Validar Payload
        try {
            $request->validate([
                'participante_type' => 'required|in:SOCIO,FAMILIAR',
                'participante_id' => 'required|integer',
                'ranking_declarado' => 'required|integer|between:0,500',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }

        // 2. Cargar Torneo y Categoria
        $torneo = Torneo::with('categoria')->findOrFail($id);

        // 3. Validar Estatus del Torneo (Debe estar en EN_INSCRIPCION)
        if ($torneo->estatus_torneo !== 'EN_INSCRIPCION') {
            return response()->json([
                'success' => false,
                'motivo' => "El torneo no está en período de inscripción."
            ], 422);
        }

        // 4. Resolver Participante y verificar Permisos del Socio
        $user = auth()->user();
        $participante = null;

        if ($request->participante_type === 'SOCIO') {
            $participante = SocioTitular::findOrFail($request->participante_id);

            // Si es rol SOCIO, solo puede inscribirse a sí mismo
            if ($user && $user->rol === 'SOCIO' && (int)$participante->id_socio !== (int)$user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para inscribir a este socio.'
                ], 403);
            }
        } else {
            $participante = MiembrosFamiliares::findOrFail($request->participante_id);

            // Si es rol SOCIO, solo puede inscribir a sus propios familiares
            if ($user && $user->rol === 'SOCIO' && (int)$participante->socio_id !== (int)$user->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes inscribir a tus propios miembros familiares.'
                ], 403);
            }
        }

        // 5. Validar Reglas de Negocio de Inscripción (Duplicados y Exclusividad Familiar)
        if ($request->participante_type === 'SOCIO') {
            // A. Duplicado del Socio
            $alreadyRegistered = ParticipantesTorneo::where('id_torneo', $id)
                ->where('participante_type', 'SOCIO')
                ->where('participante_id', $participante->id_socio)
                ->exists();
            if ($alreadyRegistered) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este participante ya está inscrito en el torneo.'
                ], 409);
            }

            // B. Si el titular intenta inscribirse, pero un familiar ya está inscrito
            $familiarIds = MiembrosFamiliares::where('socio_id', $participante->id_socio)->pluck('id_miembro');
            if ($familiarIds->isNotEmpty()) {
                $familiarRegistered = ParticipantesTorneo::where('id_torneo', $id)
                    ->where('participante_type', 'FAMILIAR')
                    ->whereIn('participante_id', $familiarIds)
                    ->exists();
                if ($familiarRegistered) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede inscribir al socio titular porque un miembro familiar ya está inscrito en este torneo.'
                    ], 409);
                }
            }
        } else {
            // A. Duplicado del Familiar
            $alreadyRegistered = ParticipantesTorneo::where('id_torneo', $id)
                ->where('participante_type', 'FAMILIAR')
                ->where('participante_id', $participante->id_miembro)
                ->exists();
            if ($alreadyRegistered) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este participante ya está inscrito en el torneo.'
                ], 409);
            }

            // B. Si un familiar intenta inscribirse, pero el titular ya está inscrito
            $socioId = $participante->socio_id;
            $socioRegistered = ParticipantesTorneo::where('id_torneo', $id)
                ->where('participante_type', 'SOCIO')
                ->where('participante_id', $socioId)
                ->exists();
            if ($socioRegistered) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede inscribir al miembro familiar porque el socio titular ya está inscrito en este torneo.'
                ], 409);
            }
        }

        // 6. Validar Elegibilidad (Edad y Género)
        // Lanza EligibilityException (422) de forma automática con render()
        $validateEligibility->execute($torneo, $participante);

        // 6. Verificar Cupo Máximo
        $confirmados = ParticipantesTorneo::where('id_torneo', $id)
            ->where('estatus_inscripcion', 'CONFIRMADO')
            ->count();

        if ($confirmados >= $torneo->cupo_maximo) {
            return response()->json([
                'success' => false,
                'message' => 'Cupo máximo alcanzado.'
            ], 409);
        }

        $tipoEntidad = $request->participante_type === 'SOCIO' ? 'SOCIO_TITULAR' : 'MIEMBRO_FAMILIAR';
        $referenciaId = $request->participante_type === 'SOCIO' ? $participante->id_socio : $participante->id_miembro;

        // 7. Insertar Registro y Manejar Duplicados
        try {
            $inscripcion = ParticipantesTorneo::create([
                'id_torneo' => $torneo->id_torneo,
                'participante_type' => $request->participante_type,
                'participante_id' => $request->participante_id,
                'estatus_inscripcion' => 'CONFIRMADO',
                'ranking_declarado' => $request->ranking_declarado,
                'id_categoria' => $torneo->id_categoria,
                'tipo_entidad' => $tipoEntidad,
                'referencia_id' => $referenciaId,
                'fecha_inscripcion' => now(),
                'estatus_participacion' => 'ACTIVO',
            ]);

            return response()->json([
                'success' => true,
                'id_participante' => $inscripcion->id_participante_torneo,
                'message' => 'Inscripción confirmada.'
            ], 201);

        } catch (QueryException $e) {
            // Código de error 23505 representa violación de restricción única en PostgreSQL
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique constraint')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este participante ya está inscrito en el torneo.'
                ], 409);
            }
            throw $e;
        }
    }
}
