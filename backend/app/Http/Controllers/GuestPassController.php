<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\SocioTitular;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class GuestPassController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'nombre_invitado' => 'required|string|max:255'
        ]);
        $id_valido = SocioTitular::where('id_socio', $request->id)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);
        }

        $count = SocioTitular::find($request->id)->invitados()->count();
        if ($count >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Has alcanzado el límite máximo de 5 invitados activos simultáneamente.'
            ], 400);
        }






        do {
            $codigoQR = 'QI' . substr(str_replace('-', '', Str::uuid()), 0, 6);
        } while (Invitados::where('codigo_qr', $codigoQR)->exists());


        $insertar = Invitados::create([
            'socio_id' => $request->id,
            'nombre_invitado' => $request->nombre_invitado,
            'codigo_qr' => $codigoQR,
        ]);
        if ($insertar) {
            return response()->json([
                'id_invitado' => $insertar->id_invitado,
                'socio_id' => $request->id,
                'nombre_invitado' => $request->nombre_invitado,
                'codigo_qr' => 'https://app.socdephub.com/guest/pass/' . $codigoQR,
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar invitado'
            ], 500);
        }

    }
}