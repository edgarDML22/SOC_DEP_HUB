<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use App\Models\SocioTitular;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class GuestPassController extends Controller
{
    public function store(Request $request)
    {

    }


    public function cancelPass(Request $request, $id)
    {
        $pase = PasesDiarios::where('invitado_id', $id)
            ->where('estatus_acceso', 'ACTIVO')
            ->first();

        if (!$pase) {
            return response()->json(['message' => 'Pase no encontrado o ya está inactivo.'], 404);
        }

        // 2. Validamos que el invitado pertenece al socio logueado (Seguridad)
        $invitado = Invitados::find($id);
        if (!$invitado || $invitado->socio_id !== $request->user()->user_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // 3. Actualizamos a EXPIRADO
        $pase->update(['estatus_acceso' => 'EXPIRADO']);

        return response()->json([
            'success' => true,
            'message' => 'Pase cancelado correctamente.'
        ], 200);
    }
}