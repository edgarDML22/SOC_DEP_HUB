<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\MiembrosFamiliares;
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
    if (!$user) return response()->json(['success' => false, 'message' => 'No autenticado'], 401);

    $perfil = null;
    $prefijo = '';
    $tipoMorph = '';

    // 1. Identificar tipo de usuario y validar estatus
    if ($user->rol === 'socio_titular') {
        $perfil = SocioTitular::find($user->user_id);
        if ($perfil->estatus_cuenta !== 'AL_CORRIENTE') {
            return response()->json(['success' => false, 'message' => 'Cuenta no al corriente'], 403);
        }
        $prefijo = 'QS';
        $tipoMorph = 'SOCIO';
    } elseif ($user->rol === 'miembro_familiar') {
        $perfil = MiembrosFamiliares::find($user->user_id);
        $prefijo = 'MF';
        $tipoMorph = 'FAMILIAR';
    }

    if (!$perfil) return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);

    // 2. Obtener o Generar el QR Permanente
    $qr = $perfil->codigoQrActivo;

    if (!$qr) {
        // Algoritmo: Prefijo + 6 caracteres aleatorios únicos
        do {
            $codigoNuevo = $prefijo . strtoupper(Str::random(6));
        } while (CodigoQr::where('codigo', $codigoNuevo)->exists());

        $qr = $perfil->codigosQr()->create([
            'codigo' => $codigoNuevo,
            'estatus' => 'ACTIVO',
            'fecha_activacion' => now()
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => ['qr_payload' => $qr->codigo]
    ], 200);
}

}