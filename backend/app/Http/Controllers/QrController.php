<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\SocioTitular;

class QrController extends Controller
{
    /**
     * Obtener el QR permanente del usuario. 
     * Si no existe (primer inicio de sesión), lo genera una única vez.
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

        // Lógica específica para Socios Titulares
        if ($user->rol === 'socio_titular') {
            $socio = SocioTitular::find($user->user_id);

            if (!$socio || $socio->estatus_cuenta !== 'AL_CORRIENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta no está al corriente. No es posible acceder al código QR.'
                ], 403);
            }

            // Buscamos su código QR a través de la relación polimórfica
            $qrActivo = $socio->codigoQrActivo;

            // Si NO tiene un código generado (ej. cuenta nueva), lo creamos de forma permanente
            if (!$qrActivo) {
                // Generamos un identificador único y seguro de 40 caracteres
                $codigoUnico = Str::random(40);

                // Insertamos en la tabla codigos_qr mediante la relación (esto se hace solo 1 vez)
                $qrActivo = $socio->codigosQr()->create([
                    'codigo' => $codigoUnico,
                    'estatus_codigo_qr' => 'ACTIVO',
                    'fecha_activacion' => now(),
                    // fecha_expiracion se queda nula porque es permanente
                ]);
            }

            // Devolvemos el código permanente extraído de la base de datos
            return response()->json([
                'success' => true,
                'data' => [
                    'qr_payload' => $qrActivo->codigo 
                ]
            ], 200);
        }

        // Aquí puedes replicar la misma lógica para 'miembro_familiar' si lo necesitas después
        return response()->json([
            'success' => false, 
            'message' => 'Rol no soportado aún.'
        ], 400);
    }
}