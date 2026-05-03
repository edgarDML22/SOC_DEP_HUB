<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocioTitular;

class NotificacionController extends Controller
{
    // GET /v1/notificaciones — lista las notificaciones del socio autenticado
    public function index(Request $request)
    {
        $socio = SocioTitular::find($request->user()->user_id);

        if (!$socio) {
            return response()->json(['success' => false, 'message' => 'Socio no encontrado'], 404);
        }

        $notificaciones = $socio->notifications()
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($n) => [
                'id'        => $n->id,
                'data'      => $n->data,
                'leida'     => !is_null($n->read_at),
                'creada_en' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success'        => true,
            'notificaciones' => $notificaciones,
            'no_leidas'      => $socio->unreadNotifications()->count(),
        ]);
    }

    // PATCH /v1/notificaciones/{id}/leer — marca una notificación como leída
    public function marcarLeida(Request $request, string $id)
    {
        $socio = SocioTitular::find($request->user()->user_id);

        if (!$socio) {
            return response()->json(['success' => false, 'message' => 'Socio no encontrado'], 404);
        }

        $notificacion = $socio->notifications()->where('id', $id)->first();

        if (!$notificacion) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
        }

        $notificacion->markAsRead();

        return response()->json(['success' => true]);
    }

    // PATCH /v1/notificaciones/leer-todas — marca todas como leídas
    public function marcarTodasLeidas(Request $request)
    {
        $socio = SocioTitular::find($request->user()->user_id);

        if (!$socio) {
            return response()->json(['success' => false, 'message' => 'Socio no encontrado'], 404);
        }

        $socio->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
