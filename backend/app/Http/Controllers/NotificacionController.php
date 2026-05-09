<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    // GET /v1/notificaciones — lista las notificaciones del socio autenticado
    public function index(Request $request)
    {
        $idSocio = $request->user()->user_id;

        $notificaciones = collect(DB::select("
            SELECT id, data, read_at, created_at
            FROM   notifications
            WHERE  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
            ORDER  BY created_at DESC
            LIMIT  6
        ", [$idSocio]))->map(fn($n) => [
            'id'        => $n->id,
            'data'      => json_decode($n->data, true),
            'leida'     => $n->read_at !== null,
            'creada_en' => $n->created_at,
        ]);

        return response()->json([
            'success'        => true,
            'notificaciones' => $notificaciones,
            'no_leidas'      => $notificaciones->filter(fn($n) => !$n['leida'])->count(),
        ]);
    }

    // PATCH /v1/notificaciones/{id}/leer — marca una notificación como leída
    public function marcarLeida(Request $request, string $id)
    {
        $affected = DB::update("
            UPDATE notifications
            SET    read_at = NOW()
            WHERE  id              = ?
              AND  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
              AND  read_at IS NULL
        ", [$id, $request->user()->user_id]);

        if (!$affected) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
        }

        return response()->json(['success' => true]);
    }

    // PATCH /v1/notificaciones/leer-todas — marca todas como leídas
    public function marcarTodasLeidas(Request $request)
    {
        DB::update("
            UPDATE notifications
            SET    read_at = NOW()
            WHERE  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
              AND  read_at IS NULL
        ", [$request->user()->user_id]);

        return response()->json(['success' => true]);
    }
}
