<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $usuario = $request->user();

        /* Making a query to get the user data*/
        $user = DB::table('users')
            ->select('perfil_id', 'rol')
            ->where('id', $usuario->id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil no encontrado'
            ], 404);
        }
        $user_final = DB::table('users as u')
            ->join('socios_titulares as s', 'u.perfil_id', '=', 's.id_socio')
            ->select('s.nombre_completo', 's.num_accion', 's.tipo_socio', 's.estatus_cuenta')
            ->first();




        return response()->json([
            "success" => true,
            "data" => [
                'nombre_completo' => $user->nombre_completo,
                'num_accion' => $user->num_accion,
                'tipo_socio' => $user->tipo_socio,
                'estatus_cuenta' => $user->estatus_cuenta
            ]
        ]);

    }
}
