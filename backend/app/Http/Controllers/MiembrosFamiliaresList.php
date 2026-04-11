<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;

class MiembrosFamiliaresList extends Controller
{
    public function show(Request $request)
    {
        $miembros = MiembrosFamiliares::where('socio_id', $request->id_socio)
            ->whereRaw("DATE_PART('year', AGE(fecha_nacimiento)) BETWEEN 3 AND 6")
            ->select('id_miembro', 'nombre_completo')
            ->get();

        if ($miembros->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron miembros familiares',
            ], 404);
        }

        return response()->json($miembros);
    }
}