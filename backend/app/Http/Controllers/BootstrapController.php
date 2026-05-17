<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BootstrapController extends Controller
{
    /**
     * Carga inicial secundaria: notificaciones, miembros familiares e invitados
     * en una sola llamada para reducir requests en el arranque del socio.
     */
    public function socioData(Request $request)
    {
        $user = $request->user();
        $socioId = $user->user_id;

        // Las tres consultas se ejecutan de forma independiente en la misma conexión.
        // Laravel no tiene async nativo, pero al correr en el mismo proceso PHP son
        // queries directas sin overhead de HTTP entre ellas.

        $notificaciones = collect(DB::select("
            SELECT id, data, read_at, created_at
            FROM   notifications
            WHERE  notifiable_type = 'SOCIO'
              AND  notifiable_id   = ?
            ORDER  BY created_at DESC
            LIMIT  6
        ", [$socioId]))->map(fn($n) => [
            'id'        => $n->id,
            'data'      => json_decode($n->data, true),
            'leida'     => $n->read_at !== null,
            'creada_en' => $n->created_at,
        ]);

        $miembros = MiembrosFamiliares::with(['codigoQrActivo:id_codigo,usuario_id,tipo_usuario,codigo,estatus'])
            ->select(['id_miembro', 'socio_id', 'nombre_completo', 'parentesco', 'fecha_nacimiento', 'genero', 'correo', 'contador_no_shows'])
            ->where('socio_id', $socioId)
            ->get()
            ->each(function ($m) {
                $m->codigo_qr = $m->codigoQrActivo?->codigo ?? 'QR_NO_ENCONTRADO';
                unset($m->codigoQrActivo);
            });

        $invitados = Invitados::with(['pase:id_pase,invitado_id,estatus_acceso,fecha_expiracion'])
            ->select(['id_invitado', 'socio_id', 'nombre_invitado', 'codigo_qr', 'correo', 'telefono'])
            ->where('socio_id', $socioId)
            ->get()
            ->map(function ($inv) {
                $pase = $inv->pase;
                $estatusAcceso = $pase?->estatus_acceso ?? 'SIN_PASE';
                if ($estatusAcceso === 'ACTIVO' && $pase->fecha_expiracion && now()->greaterThan($pase->fecha_expiracion)) {
                    $estatusAcceso = 'EXPIRADO';
                }
                return [
                    'id'               => $inv->id_invitado,
                    'nombre'           => $inv->nombre_invitado,
                    'codigo_qr'        => $inv->codigo_qr,
                    'correo'           => $inv->correo,
                    'telefono'         => $inv->telefono,
                    'estatus_acceso'   => $estatusAcceso,
                    'fecha_expiracion' => $pase?->fecha_expiracion,
                    'id_pase'          => $pase?->id_pase,
                    'fecha_registro'   => $inv->created_at,
                    'deleted_at'       => $inv->deleted_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'notificaciones'    => $notificaciones,
                'no_leidas'         => $notificaciones->filter(fn($n) => !$n['leida'])->count(),
                'miembros_familiares' => $miembros,
                'invitados'         => $invitados,
            ]
        ]);
    }
}
