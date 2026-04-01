<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class QrController extends Controller
{
    /**
     * Generar un payload encriptado para el Código QR.
     *
     * @param \Illuminate\Http\Request $request
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

        // Crear payload con el ID del usuario y el timestamp actual para evitar clonaciones
        // Se utiliza el ID del modelo User estándar (que puede mapear a id_usuario)
        $userId = $user->id ?? $user->id_usuario ?? $user->getKey();
        $timestamp = now()->timestamp;
        
        $payloadData = $userId . '|' . $timestamp;

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
