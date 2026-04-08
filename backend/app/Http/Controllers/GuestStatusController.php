<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\SocioTitular;

class GuestStatusController extends Controller
{
    public function store(Request $request)
    {
        $id_valido = SocioTitular::where('id_socio', $request->id)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);

        }
        $request->validate([
            'status' => 'required|string|in:ACTIVO,INACTIVO,EXPIRADO',
        ]);

        $status = $request->status;

        $invitados = SocioTitular::find($request->id)
            ->invitados()
            ->whereHas('pase', function ($query) use ($status) {
                $query->where('estatus_acceso', $status);
            })
            ->get();

        if ($invitados == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron invitados con ese estatus'
            ], 404);
        }
        return response()->json([
            'data' => $invitados->map(function ($inv) {
                $pase = $inv->pase;
                return [
                    'nombre' => $inv->nombre_invitado,
                    'id' => $inv->id_invitado,
                    'codigo_qr' => $inv->codigo_qr,
                    'correo' => $inv->correo,
                    'telefono' => $inv->telefono,
                    'estatus_acceso' => $pase?->estatus_acceso,
                    'fecha_expiracion' => $pase?->fecha_expiracion,
                ];
            })
        ], 200);



    }
}