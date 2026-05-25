<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\RegistroAsistencia;
use App\Models\SesionActiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterEventController extends Controller
{
    /**
     * POST /api/v1/instructor/register-event
     *
     * Registra la asistencia de un socio a una sesión.
     * El QR contiene el payload del socio; el código ya fue validado
     * por el frontend (regex OS|MF|OI + 6 chars) antes de llegar aquí.
     *
     * Persistencia: 100% PostgreSQL — tabla registros_asistencia.
     */
    public function register_event(Request $request): JsonResponse
    {
        $request->validate([
            'id'        => 'required|string',
            'id_sesion' => 'required|integer',
            'fase'      => 'required|in:ingreso,cierre',
        ]);

        // ── 1. Resolver el socio a partir del payload QR ─────────────────────
        $codigoQr = CodigoQr::where('codigo', $request->id)
            ->where('estatus', 'ACTIVO')
            ->first();

        if (!$codigoQr) {
            return response()->json([
                'success' => false,
                'message' => 'Código QR no válido o expirado.',
            ], 404);
        }

        // ── 2. Verificar que la sesión existe ─────────────────────────────────
        $sesion = SesionActiva::withoutGlobalScopes()
            ->find($request->id_sesion);

        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada.',
            ], 404);
        }

        // ── 3. Evitar doble registro en la misma sesión/fase ─────────────────
        $yaRegistrado = RegistroAsistencia::where('id_sesion',  $sesion->id_sesion)
            ->where('id_usuario',  $codigoQr->usuario_id)
            ->where('tipo_usuario', $codigoQr->tipo_usuario)
            ->where('metodo_registro', $request->fase)
            ->exists();

        if ($yaRegistrado) {
            return response()->json([
                'success' => false,
                'message' => 'Este socio ya fue registrado en esta sesión.',
            ], 409);
        }

        // ── 4. Crear el registro en PostgreSQL ────────────────────────────────
        $registro = RegistroAsistencia::create([
            'id_sesion'        => $sesion->id_sesion,
            'id_usuario'       => $codigoQr->usuario_id,
            'tipo_usuario'     => $codigoQr->tipo_usuario,
            'metodo_registro'  => $request->fase,
            'asistencia'       => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada correctamente.',
            'data'    => [
                'id_registro'         => $registro->id_registro,
                'id_sesion'           => $registro->id_sesion,
                'tipo_usuario'        => $registro->tipo_usuario,
                'metodo_registro'     => $registro->metodo_registro,
                'fecha_hora_registro' => $registro->fecha_hora_registro,
            ],
        ], 201);
    }
}
