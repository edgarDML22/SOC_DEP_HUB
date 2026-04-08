<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;
use App\Models\SocioTitular;

class MiembrosFamiliaresController extends Controller
{


    public function show(Request $request)
    {
        $id_valido = SocioTitular::where('id_socio', $request->id)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);

        }
        $miembros = MiembrosFamiliares::where('socio_id', $request->id)
            ->get();
        return response()->json($miembros);
    }


}