<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AsistenciaController extends Controller
{
    public function validarAcceso(Request $request)
    {
        $request->validate([
            'qr_payload' => 'required|string',
        ]);

        try {
            $decrypted = Crypt::decryptString($request->input('qr_payload'));
            
            // The payload might be a JSON containing socio_id, or just the socio_id string itself.
            $data = json_decode($decrypted, true);
            $socioId = (json_last_error() === JSON_ERROR_NONE && isset($data['socio_id'])) 
                ? $data['socio_id'] 
                : $decrypted;

        } catch (DecryptException $e) {
            return response()->json([
                'success' => false,
                'message' => 'QR inválido o alterado'
            ], 400);
        }

        $socio = SocioTitular::where('id_socio', $socioId)->first();

        if (!$socio) {
            return response()->json([
                'success' => false,
                'message' => 'Socio no encontrado'
            ], 404);
        }

        if (strcasecmp($socio->estatus_cuenta, 'Inactivo') === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Socio suspendido o inactivo'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'socio_id' => $socio->id_socio,
            'nombre_completo' => $socio->nombre_completo,
            'estatus' => $socio->estatus_cuenta
        ], 200);
    }
}
