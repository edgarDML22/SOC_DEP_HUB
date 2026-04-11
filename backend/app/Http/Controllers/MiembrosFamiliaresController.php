<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;
use App\Models\SocioTitular;

class MiembrosFamiliaresController extends Controller
{


    public function show(Request $request)
    {
        $id_socio = $request->user()->user_id;

        $id_valido = SocioTitular::where('id_socio', $id_socio)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);
        }
        $miembros = MiembrosFamiliares::where('socio_id', $id_socio)
            ->get();
        return response()->json($miembros);
    }

    public function store(Request $request){

    }

    public function update(Request $request){

    }

    public function destroy(Request $request){

    }

    
}
