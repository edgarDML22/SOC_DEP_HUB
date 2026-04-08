<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class QrController extends Controller
{
    /**
     * Generar un payload encriptado para el Código QR de acceso.
     *
     * El payload contiene:
     *   - user_id  → FK hacia la tabla del perfil real (socios_titulares, miembros_familiares, etc.)
     *   - rol      → enum_tipo_usuario, para que el scanner sepa en qué tabla buscar
     *   - timestamp → para evitar clonaciones / replay attacks
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQrPayload(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Verificar que el socio titular tenga cuenta al corriente antes de generar el QR.
        // Solo aplica para socios titulares; otros roles no tienen estatus_cuenta.
        if ($user->rol === 'socio_titular') {
            $socio = \App\Models\SocioTitular::find($user->user_id);

            if (!$socio || $socio->estatus_cuenta !== 'AL_CORRIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta no está al corriente. No es posible generar el código QR.'
                ], 403);
            }
        }

        // Construir el payload con el ID del perfil (no el ID de la tabla users),
        // el rol para saber en qué tabla buscar, y el timestamp actual.
        $payloadData = $user->user_id . '|' . $user->rol . '|' . now()->timestamp;

        // Encriptar el payload
        $encryptedPayload = Crypt::encryptString($payloadData);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_payload' => $encryptedPayload
            ]
        ], 200);
    }
}
