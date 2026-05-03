<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Amistades;
use App\Models\SocioTitular;
use App\Models\Reservacion;
use App\Notifications\SolicitudAmistadNotification;

class FriendsController extends Controller
{
    // MÉTODO 1: Mostrar lista de amistades (aceptadas y pendientes)
    public function show(Request $request)
    {
        $socioId = (int) $request->user()->user_id;

        // Agrupamos el orWhere para evitar conflictos con eager load
        $amistades = Amistades::with(['solicitante', 'receptor'])
            ->where(function ($q) use ($socioId) {
                $q->where('solicitante_id', $socioId)
                  ->orWhere('receptor_id', $socioId);
            })
            ->get();

        $formateado = $amistades->map(function ($amistad) use ($socioId) {
            // Forzamos a entero ambos lados para comparación estricta
            $esSolicitante = (int) $amistad->solicitante_id === $socioId;
            $amigo = $esSolicitante ? $amistad->receptor : $amistad->solicitante;

            return [
                'id_amistad'       => $amistad->id_amistad,
                'id_amigo'         => $amigo ? $amigo->id_socio : null,
                'nombre_amigo'     => $amigo ? $amigo->nombre_completo : 'Desconocido',
                'estado'           => $amistad->estado,
                'solicitado_por_mi' => $esSolicitante,
                'solicitante_id'   => $amistad->solicitante_id, // útil para depurar
                'receptor_id'      => $amistad->receptor_id,   // útil para depurar
                'mi_id'            => $socioId,                 // útil para depurar
                'created_at'       => $amistad->created_at
            ];
        })->filter(function ($amistad) {
            return $amistad['estado'] !== 'RECHAZADA' && $amistad['estado'] !== 'BLOQUEADA';
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formateado
        ], 200);
    }

    // MÉTODO 2: Enviar solicitud de amistad
    public function store(Request $request)
    {
        $request->validate([
            'receptor_id' => 'required|integer|exists:socios_titulares,id_socio'
        ]);

        $socioId = $request->user()->user_id;
        $receptorId = $request->receptor_id;

        if ($socioId == $receptorId) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes enviarte una solicitud a ti mismo'
            ], 400);
        }

        // Verificar si ya existe una solicitud o amistad
        $existe = Amistades::where(function ($q) use ($socioId, $receptorId) {
            $q->where('solicitante_id', $socioId)->where('receptor_id', $receptorId);
        })->orWhere(function ($q) use ($socioId, $receptorId) {
            $q->where('solicitante_id', $receptorId)->where('receptor_id', $socioId);
        })->first();

        if ($existe) {
            // Si fue rechazada, el registro es basura: borrarlo y permitir reenvío
            if ($existe->estado === 'RECHAZADA') {
                $existe->delete();
            } else {
                // PENDIENTE o ACEPTADA → sí bloqueamos
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una relación de amistad o solicitud pendiente'
                ], 400);
            }
        }

        $amistad = Amistades::create([
            'solicitante_id' => $socioId,
            'receptor_id' => $receptorId,
            'estado' => 'PENDIENTE',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Notificar al receptor que recibió una solicitud
        $solicitante = $request->user()->socioTitular ?? \App\Models\SocioTitular::find($socioId);
        $receptor    = \App\Models\SocioTitular::find($receptorId);
        if ($solicitante && $receptor) {
            $receptor->notify(new SolicitudAmistadNotification(
                tipo: 'SOLICITUD_ENVIADA',
                nombre_remitente: $solicitante->nombre_completo,
                id_remitente: (int) $socioId,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada correctamente',
            'data' => $amistad
        ], 201);
    }

    // MÉTODO 3: Eliminar amigo (con validación de reservas activas)
    public function destroy(Request $request)
    {
        $request->validate([
            'id_amistad' => 'required|integer|exists:amistades,id_amistad'
        ]);

        $socioId = $request->user()->user_id;

        $amistad = Amistades::where('id_amistad', $request->id_amistad)
            ->where(function ($q) use ($socioId) {
                $q->where('solicitante_id', $socioId)
                  ->orWhere('receptor_id', $socioId);
            })->first();

        if (!$amistad) {
            return response()->json([
                'success' => false,
                'message' => 'Amistad no encontrada o no autorizada'
            ], 404);
        }

        // Determinar el ID del amigo
        $amigoId = ($amistad->solicitante_id == $socioId) ? $amistad->receptor_id : $amistad->solicitante_id;

        // Validar si el amigo está en una reservación ACTIVA o PENDIENTE
        $hasReservation = Reservacion::where('id_socio_titular', $socioId)
            ->whereIn('estatus_operativo', ['PENDIENTE', 'ACTIVA'])
            ->whereJsonContains('acompanantes_draft', ['id' => $amigoId, 'tipo' => 'amigo'])
            ->exists();

        if ($hasReservation) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar a este amigo porque tienes una reservación activa o pendiente con él.'
            ], 400);
        }

        $amistad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amigo eliminado correctamente de tu lista'
        ], 200);
    }

    // MÉTODO 4: Aceptar solicitud
    public function accept(Request $request)
    {
        $request->validate([
            'id_amistad' => 'required|integer|exists:amistades,id_amistad'
        ]);

        $socioId = $request->user()->user_id;

        $amistad = Amistades::where('id_amistad', $request->id_amistad)
            ->where('receptor_id', $socioId)
            ->where('estado', 'PENDIENTE')
            ->first();

        if (!$amistad) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada o no válida para aceptar'
            ], 404);
        }

        $amistad->estado = 'ACEPTADA';
        $amistad->updated_at = now();
        $amistad->save();

        // Notificar al solicitante que su solicitud fue aceptada
        $receptor    = \App\Models\SocioTitular::find($socioId);
        $solicitante = \App\Models\SocioTitular::find($amistad->solicitante_id);
        if ($receptor && $solicitante) {
            $solicitante->notify(new SolicitudAmistadNotification(
                tipo: 'SOLICITUD_ACEPTADA',
                nombre_remitente: $receptor->nombre_completo,
                id_remitente: $socioId,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de amistad aceptada',
            'data' => $amistad
        ], 200);
    }

    // MÉTODO 5: Rechazar solicitud
    public function reject(Request $request)
    {
        $request->validate([
            'id_amistad' => 'required|integer|exists:amistades,id_amistad'
        ]);

        $socioId = $request->user()->user_id;

        $amistad = Amistades::where('id_amistad', $request->id_amistad)
            ->where('receptor_id', $socioId)
            ->where('estado', 'PENDIENTE')
            ->first();

        if (!$amistad) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada o no válida para rechazar'
            ], 404);
        }

        $amistad->estado = 'RECHAZADA';
        $amistad->updated_at = now();
        $amistad->save();

        // Notificar al solicitante que su solicitud fue rechazada
        $receptor    = \App\Models\SocioTitular::find($socioId);
        $solicitante = \App\Models\SocioTitular::find($amistad->solicitante_id);
        if ($receptor && $solicitante) {
            $solicitante->notify(new SolicitudAmistadNotification(
                tipo: 'SOLICITUD_RECHAZADA',
                nombre_remitente: $receptor->nombre_completo,
                id_remitente: $socioId,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de amistad rechazada',
            'data' => $amistad
        ], 200);
    }

    // MÉTODO 6: Cancelar solicitud enviada (solo el solicitante, solo PENDIENTE)
    public function cancel(Request $request)
    {
        $request->validate([
            'id_amistad' => 'required|integer|exists:amistades,id_amistad'
        ]);

        $socioId = $request->user()->user_id;

        $amistad = Amistades::where('id_amistad', $request->id_amistad)
            ->where('solicitante_id', $socioId)
            ->where('estado', 'PENDIENTE')
            ->first();

        if (!$amistad) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada o no autorizada para cancelar'
            ], 404);
        }

        $amistad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de amistad cancelada'
        ], 200);
    }
}
