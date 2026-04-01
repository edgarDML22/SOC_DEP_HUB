<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $data = null;

        switch ($usuario->rol) {
            case 'socio_titular':
                $perfil = DB::table('socios_titulares')
                    ->select('nombre_completo', 'numero_accion', 'tipo_socio', 'estatus_cuenta')
                    ->where('id_socio', $usuario->user_id)
                    ->first();

                if ($perfil) {
                    $data = [
                        'nombre_completo' => $perfil->nombre_completo,
                        'numero_accion' => $perfil->numero_accion,
                        'tipo_socio' => $perfil->tipo_socio,
                        'estatus_cuenta' => $perfil->estatus_cuenta
                    ];
                }
                break;

            case 'miembro_familiar':
                $perfil = DB::table('miembros_familiares as mf')
                    ->join('socios_titulares as st', 'mf.socio_id', '=', 'st.id_socio')
                    ->select(
                    'mf.nombre_completo',
                    'st.numero_accion',
                    'st.tipo_socio',
                    'st.estatus_cuenta'
                )
                    ->where('mf.id_miembro', $usuario->user_id)
                    ->first();

                if ($perfil) {
                    $data = [
                        'nombre_completo' => $perfil->nombre_completo,
                        'numero_accion' => $perfil->numero_accion,
                        'tipo_socio' => $perfil->tipo_socio,
                        'estatus_cuenta' => $perfil->estatus_cuenta
                    ];
                }
                break;

            case 'instructor':
                $perfil = DB::table('instructores')
                    ->select('nombre_completo', 'estatus')
                    ->where('id_instructor', $usuario->user_id)
                    ->first();

                if ($perfil) {
                    $data = [
                        'nombre_completo' => $perfil->nombre_completo,
                        'num_accion' => null,
                        'tipo_socio' => null,
                        'estatus_cuenta' => $perfil->estatus
                    ];
                }
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no válido'
                ], 400);
        }

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil no encontrado'
            ], 404);
        }

        // Esta es la línea que traía la versión Incoming y que debemos conservar
        $data['fecha_actualizacion_password'] = $usuario->updated_at;

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}