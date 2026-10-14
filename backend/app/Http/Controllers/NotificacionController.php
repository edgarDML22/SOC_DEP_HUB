<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    // GET /v1/notificaciones — lista las notificaciones del socio autenticado (o por id_socio para pruebas)
    public function index(Request $request)
    {
        $idSocio = $request->user()?->user_id ?? $request->input('id_socio') ?? $request->input('socio_id');

        if (!$idSocio) {
            $idSocio = DB::table('notifications')
                ->where('notifiable_type', 'SOCIO')
                ->latest('created_at')
                ->value('notifiable_id');
        }

        if (!$idSocio) {
            return response()->json([
                'success'        => true,
                'notificaciones' => [],
                'no_leidas'      => 0,
            ]);
        }

        $notificaciones = collect(DB::select("
            SELECT id, type, notifiable_type, notifiable_id, data, read_at, created_at
            FROM   notifications
            WHERE  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
            ORDER  BY created_at DESC
            LIMIT  15
        ", [$idSocio]))->map(function ($n) {
            $data = is_string($n->data) ? json_decode($n->data, true) : (array) $n->data;

            return [
                'id'         => $n->id,
                'tipo'       => $data['tipo'] ?? 'SISTEMA',
                'titulo'     => $data['titulo'] ?? 'Notificación',
                'mensaje'    => $data['mensaje'] ?? '',
                'severidad'  => $data['severidad'] ?? 'info',
                'data'       => $data['data'] ?? $data,
                'leida'      => $n->read_at !== null,
                'creada_en'  => $n->created_at,
            ];
        });

        return response()->json([
            'success'        => true,
            'id_socio'       => (int) $idSocio,
            'notificaciones' => $notificaciones,
            'no_leidas'      => $notificaciones->filter(fn($n) => !$n['leida'])->count(),
        ]);
    }

    // PATCH /v1/notificaciones/{id}/leer — marca una notificación como leída
    public function marcarLeida(Request $request, string $id)
    {
        $idSocio = $request->user()?->user_id ?? $request->input('id_socio') ?? $request->input('socio_id');

        $query = "
            UPDATE notifications
            SET    read_at = NOW()
            WHERE  id              = ?
              AND  notifiable_type = 'SOCIO'
              AND  read_at IS NULL
        ";
        $bindings = [$id];

        if ($idSocio) {
            $query .= " AND notifiable_id = ?";
            $bindings[] = $idSocio;
        }

        $affected = DB::update($query, $bindings);

        if (!$affected) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada o ya leída'], 404);
        }

        return response()->json(['success' => true]);
    }

    // PATCH /v1/notificaciones/leer-todas — marca todas como leídas
    public function marcarTodasLeidas(Request $request)
    {
        $idSocio = $request->user()?->user_id ?? $request->input('id_socio') ?? $request->input('socio_id');

        if (!$idSocio) {
            return response()->json(['success' => false, 'message' => 'Socio no especificado'], 400);
        }

        DB::update("
            UPDATE notifications
            SET    read_at = NOW()
            WHERE  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
              AND  read_at IS NULL
        ", [$idSocio]);

        return response()->json(['success' => true]);
    }
}
