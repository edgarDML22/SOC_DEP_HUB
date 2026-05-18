<?php

namespace App\Http\Controllers;

use App\Actions\ValidarElegibilidadAction;
use App\Jobs\NotificarInvitacionEquipoJob;
use App\Jobs\NotificarRechazoEquipoJob;
use App\Models\Amistades;
use App\Models\EquiposTorneo;
use App\Models\ParticipantesTorneo;
use App\Models\SocioTitular;
use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipoTorneoController extends Controller
{
    public function show($id_equipo)
    {
        $equipo = \App\Models\EquiposTorneo::with([
            'torneo.categoria',
            'torneo.disciplina',
            'participantes'
        ])->find($id_equipo);

        if (!$equipo) {
            return response()->json(['message' => 'Equipo no encontrado'], 404);
        }

        $capitanPart = $equipo->participantes->where('estatus_inscripcion', 'CONFIRMADA')->first();
        $companeroPart = $equipo->participantes->where('estatus_inscripcion', '!=', 'CONFIRMADA')->first();

        $capitanModel = null;
        if ($capitanPart && $capitanPart->participante_type === 'SOCIO') {
            $capitanModel = \App\Models\SocioTitular::find($capitanPart->participante_id);
        }

        $companeroModel = null;
        if ($companeroPart && $companeroPart->participante_type === 'SOCIO') {
            $companeroModel = \App\Models\SocioTitular::find($companeroPart->participante_id);
        }

        $data = [
            'id_equipo' => $equipo->id_equipo_torneo,
            'nombre_equipo' => $equipo->nombre_equipo,
            'estatus_equipo' => $equipo->estatus_equipo,
            'capitan' => $capitanModel,
            'companero' => $companeroModel,
            'torneo' => $equipo->torneo
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function crearEquipo(Request $request, $id_torneo)
    {
        $request->validate([
            'id_socio_companero' => 'required|integer',
            'ranking_capitan' => 'required|integer',
            'ranking_companero' => 'required|integer',
            'nombre_equipo' => 'nullable|string|max:150'
        ]);

        $torneo = Torneo::find($id_torneo);

        if (!$torneo) {
            return response()->json([
                'message' => 'Torneo no encontrado.'
            ], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 419);
        }

        $usuarioId = $user->user_id;

        $companeroId = $request->id_socio_companero;

        /*
        |--------------------------------------------------------------------------
        | Validar amistad aceptada
        |--------------------------------------------------------------------------
        */

        $amistad = Amistades::where('estado', 'ACEPTADA')
            ->where(function ($query) use ($usuarioId, $companeroId) {
                $query->where(function ($q) use ($usuarioId, $companeroId) {
                    $q->where('solicitante_id', $usuarioId)
                      ->where('receptor_id', $companeroId);
                })->orWhere(function ($q) use ($usuarioId, $companeroId) {
                    $q->where('solicitante_id', $companeroId)
                      ->where('receptor_id', $usuarioId);
                });
            })
            ->exists();

        if (!$amistad) {
            return response()->json([
                'message' => "No existe amistad ACEPTADA con este socio. (Tú: $usuarioId, Amigo: $companeroId)"
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar que el capitán no esté ya en otro equipo del mismo torneo
        |--------------------------------------------------------------------------
        */

        $capitanYaInscrito = ParticipantesTorneo::where('participante_id', $usuarioId)
            ->where('id_torneo', $id_torneo)
            ->whereIn('estatus_inscripcion', ['LISTA_ESPERA', 'CONFIRMADA'])
            ->exists();

        if ($capitanYaInscrito) {
            return response()->json([
                'message' => 'Ya estás inscrito en otro equipo de este torneo.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar que el compañero no esté en otro equipo
        |--------------------------------------------------------------------------
        */

        $yaExiste = ParticipantesTorneo::where('participante_id', $companeroId)
            ->where('id_torneo', $id_torneo)
            ->whereIn('estatus_inscripcion', ['LISTA_ESPERA', 'CONFIRMADA'])
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'message' => 'El compañero ya está inscrito en otro equipo de este torneo.'
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar elegibilidad del compañero (edad, género, penalización, cuenta)
        |--------------------------------------------------------------------------
        */

        try {
            app(ValidarElegibilidadAction::class)->execute(
                socioId: (int) $companeroId,
                idCategoria: (int) $torneo->id_categoria
            );
        } catch (ValidationException $e) {
            $motivo = collect($e->errors())->flatten()->first()
                ?? 'El compañero no cumple los requisitos del torneo.';

            return response()->json(['message' => $motivo], 422);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Crear capitán primero
            |--------------------------------------------------------------------------
            */

            // Verificar que el torneo tenga categoria asignada
            if (is_null($torneo->id_categoria)) {
                throw new \Exception('El torneo no tiene categoría asignada.');
            }

            $capitan = ParticipantesTorneo::create([
                'id_categoria'          => $torneo->id_categoria,
                'tipo_entidad'          => 'SOCIO_TITULAR',
                'referencia_id'         => $usuarioId,
                'siembra_ranking'       => $request->ranking_capitan,
                'fecha_inscripcion'     => now(),
                'estatus_participacion' => 'ACTIVO',
                'id_torneo'             => $id_torneo,
                'participante_type'     => 'SOCIO',
                'participante_id'       => $usuarioId,
                'ranking_declarado'     => $request->ranking_capitan,
                'estatus_inscripcion'   => 'CONFIRMADA',
            ]);
            $capitan->refresh();

            /*
            |--------------------------------------------------------------------------
            | Crear equipo – id_interno secuencial por categoría
            |--------------------------------------------------------------------------
            */

            $proximoIdInterno = (EquiposTorneo::where('id_categoria', $torneo->id_categoria)
                ->max('id_interno') ?? 0) + 1;

            $equipo = EquiposTorneo::create([
                'id_participante_torneo' => $capitan->id_participante_torneo,
                'id_categoria'           => $torneo->id_categoria,
                'tipo_entidad'           => 'SOCIO_TITULAR',
                'referencia_id'          => $usuarioId,
                'id_interno'             => $proximoIdInterno,
                'siembra_ranking'        => $request->ranking_capitan,
                'posicion_actual'        => 0,
                'puntos_torneo'          => 0,
                'estatus_participacion'  => 'ACTIVO',
                'fecha_registro'         => now(),
                'id_torneo'              => $id_torneo,
                'nombre_equipo'          => $request->nombre_equipo,
                'estatus_equipo'         => 'PENDIENTE',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Actualizar capitán con id_equipo
            |--------------------------------------------------------------------------
            */

            $capitan->update([
                'id_equipo' => $equipo->id_equipo_torneo
            ]);

            /*
            |--------------------------------------------------------------------------
            | Crear compañero pendiente
            |--------------------------------------------------------------------------
            */

            ParticipantesTorneo::create([
                'id_categoria'          => $torneo->id_categoria,
                'tipo_entidad'          => 'SOCIO_TITULAR',
                'referencia_id'         => $companeroId,
                'siembra_ranking'       => $request->ranking_companero,
                'fecha_inscripcion'     => now(),
                'estatus_participacion' => 'ACTIVO',
                'id_torneo'             => $id_torneo,
                'participante_type'     => 'SOCIO',
                'participante_id'       => $companeroId,
                'ranking_declarado'     => $request->ranking_companero,
                'id_equipo'             => $equipo->id_equipo_torneo,
                'estatus_inscripcion'   => 'LISTA_ESPERA',
            ]);

            DB::commit();

            // Dispatch notificación al compañero sin bloquear el request
            $capitanModel   = SocioTitular::find($usuarioId);
            $companeroModel = SocioTitular::find($companeroId);

            if ($capitanModel && $companeroModel) {
                NotificarInvitacionEquipoJob::dispatch($equipo, $capitanModel, $companeroModel);
            }

            return response()->json([
                'id_equipo' => $equipo->id_equipo_torneo,
                'estatus_equipo' => 'PENDIENTE'
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear equipo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function responderInvitacion(Request $request, $id_torneo, $id_equipo)
    {
        $request->validate([
            'decision' => 'required|in:ACEPTAR,RECHAZAR'
        ]);

        /*
        |--------------------------------------------------------------------------
        | El compañero (Socio B) es SIEMPRE quien está en LISTA_ESPERA.
        | Buscamos su registro directamente por estatus, sin depender del ID del
        | usuario autenticado (que en producción sería auth()->user()->user_id).
        |--------------------------------------------------------------------------
        */

        $equipo = EquiposTorneo::find($id_equipo);

        if (!$equipo) {
            return response()->json([
                'message' => 'Equipo no encontrado.'
            ], 404);
        }

        // Solo se puede responder si el equipo está PENDIENTE
        if ($equipo->estatus_equipo !== 'PENDIENTE') {
            return response()->json([
                'message' => 'El equipo ya no está en estado PENDIENTE.'
            ], 422);
        }

        // El compañero a responder es el participante en LISTA_ESPERA
        $companero = ParticipantesTorneo::where('id_equipo', $id_equipo)
            ->where('estatus_inscripcion', 'LISTA_ESPERA')
            ->first();

        if (!$companero) {
            return response()->json([
                'message' => 'No hay invitación pendiente de respuesta para este equipo.'
            ], 404);
        }

        if ($request->decision === 'ACEPTAR') {

            $companero->update([
                'estatus_inscripcion'   => 'CONFIRMADA',
                'estatus_participacion' => 'ACTIVO',
            ]);

            $equipo->update([
                'estatus_equipo' => 'CONFIRMADO',
            ]);
        }

        if ($request->decision === 'RECHAZAR') {

            $companero->update([
                'estatus_inscripcion'   => 'CANCELADA',
                'estatus_participacion' => 'ELIMINADO',
            ]);

            $equipo->update([
                'estatus_equipo' => 'PENDIENTE',
            ]);

            // Notificar al capitán del rechazo
            $capitanParticipante = ParticipantesTorneo::where('id_equipo', $id_equipo)
                ->where('estatus_inscripcion', 'CONFIRMADA')
                ->first();

            if ($capitanParticipante) {
                $capitanModel   = SocioTitular::find($capitanParticipante->participante_id);
                $companeroModel = SocioTitular::find($companero->participante_id);

                if ($capitanModel && $companeroModel) {
                    NotificarRechazoEquipoJob::dispatch($equipo, $capitanModel, $companeroModel);
                }
            }
        }

        return response()->json([
            'estatus_equipo' => $equipo->fresh()->estatus_equipo
        ], 200);
    }

    public function reasignarCompanero(Request $request)
    {
        $request->validate([
            'id_equipo' => 'required|integer',
            'id_nuevo_companero' => 'required|integer',
            'ranking_nuevo_companero' => 'required|integer'
        ]);

        $equipo = EquiposTorneo::find($request->id_equipo);

        if (!$equipo) {
            return response()->json([
                'message' => 'Equipo no encontrado.'
            ], 404);
        }

        if ($equipo->estatus_equipo !== 'PENDIENTE') {
            return response()->json([
                'message' => 'El equipo no está pendiente.'
            ], 422);
        }

        $rechazado = ParticipantesTorneo::where(
            'id_equipo',
            $request->id_equipo
        )
            ->where('estatus_inscripcion', 'CANCELADA')
            ->first();

        if (!$rechazado) {
            return response()->json([
                'message' => 'No existe rechazo previo.'
            ], 422);
        }

        $rechazado->delete();

        ParticipantesTorneo::create([
            'id_categoria'          => $rechazado->id_categoria,
            'tipo_entidad'          => 'SOCIO_TITULAR',
            'referencia_id'         => $request->id_nuevo_companero,
            'siembra_ranking'       => $request->ranking_nuevo_companero,
            'fecha_inscripcion'     => now(),
            'estatus_participacion' => 'ACTIVO',
            'id_torneo'             => $rechazado->id_torneo,
            'participante_type'     => 'SOCIO',
            'participante_id'       => $request->id_nuevo_companero,
            'id_equipo'             => $request->id_equipo,
            'estatus_inscripcion'   => 'LISTA_ESPERA',
        ]);

        // Notificar al nuevo compañero sin bloquear el request
        $capitanParticipante = ParticipantesTorneo::where('id_equipo', $request->id_equipo)
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->first();

        if ($capitanParticipante) {
            $capitanModel        = SocioTitular::find($capitanParticipante->participante_id);
            $nuevoCompaneroModel = SocioTitular::find($request->id_nuevo_companero);

            if ($capitanModel && $nuevoCompaneroModel) {
                NotificarInvitacionEquipoJob::dispatch($equipo, $capitanModel, $nuevoCompaneroModel);
            }
        }

        return response()->json([
            'id_equipo' => $equipo->id_equipo_torneo,
            'estatus_equipo' => 'PENDIENTE',
            'message' => 'Invitación enviada al nuevo compañero.'
        ], 200);
    }
}