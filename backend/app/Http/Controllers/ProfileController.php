<?php

namespace App\Http\Controllers;

use App\Models\SocioTitular;
use App\Models\Instructor;
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

                $perfil = SocioTitular::find($usuario->user_id);

                if (!$perfil) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Perfil no encontrado'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id_socio' => $perfil->id_socio,
                        'numero_accion' => $perfil->numero_accion,
                        'nombre_completo' => $perfil->nombre_completo,
                        'tipo_socio' => $perfil->tipo_socio,
                        'modalidad_plan' => $perfil->modalidad_plan,
                        'estatus_cuenta' => $perfil->estatus_cuenta,
                        'correo_electronico' => $perfil->correo_electronico,
                        'fecha_nacimiento' => $perfil->fecha_nacimiento,
                        'genero' => $perfil->genero,
                        'fecha_afiliacion' => $perfil->fecha_afiliacion,
                        'contador_no_shows' => $perfil->contador_no_shows,
                        'estatus_penalizacion' => $perfil->estatus_penalizacion,
                        'fecha_fin_penalizacion' => $perfil->fecha_fin_penalizacion,
                        'retrasos_ludoteca' => $perfil->retrasos_ludoteca,
                    ]
                ]);
                break;

            case 'miembro_familiar':
                $perfil = DB::table('miembros_familiares as mf')
                    ->join('socios_titulares as st', 'mf.socio_id', '=', 'st.id_socio')
                    ->select(
                        'mf.id_miembro',
                        'mf.nombre_completo',
                        'mf.socio_id',
                        'st.numero_accion',
                        'st.tipo_socio',
                        'st.modalidad_plan',
                        'st.estatus_cuenta',
                        'st.estatus_penalizacion',
                        'st.fecha_fin_penalizacion'
                    )
                    ->where('mf.id_miembro', $usuario->user_id)
                    ->first();

                if ($perfil) {
                    $data = [
                        'id_miembro' => $perfil->id_miembro,
                        'id_socio' => $perfil->socio_id,
                        'nombre_completo' => $perfil->nombre_completo,
                        'numero_accion' => $perfil->numero_accion,
                        'tipo_socio' => $perfil->tipo_socio,
                        'modalidad_plan' => $perfil->modalidad_plan,
                        'estatus_cuenta' => $perfil->estatus_cuenta,
                        'estatus_penalizacion' => $perfil->estatus_penalizacion ?? null,
                        'fecha_fin_penalizacion' => $perfil->fecha_fin_penalizacion ?? null,
                    ];
                }
                break;

            case 'instructor':
                $perfil = Instructor::where('id_instructor', $usuario->user_id)
                    ->first();

                if ($perfil) {
                    $data = [
                        'nombre_completo' => $perfil->nombre_completo,
                        'estatus_cuenta' => $perfil->estatus,
                        'correo_electronico' => $perfil->correo_electronico,
                        'telefono' => $perfil->telefono,
                        'fecha_afiliacion' => $perfil->fecha_afiliacion,
                        'fecha_nacimiento' => $perfil->fecha_nacimiento,
                        'rol' => 'Instructor',
                    ];
                }
                break;
            case 'gerente':
            case 'subgerente':

                $perfil = DB::table('gerentes')
                    ->select('id_empleado', 'nombre_completo', 'estatus')
                    ->where('id_empleado', $usuario->user_id)
                    ->first();

                if (!$perfil) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gerente no encontrado',
                        'user_id' => $usuario->user_id
                    ], 404);
                }
                $id = DB::table('users')
                    ->where('user_id', $perfil->id_empleado)
                    ->whereIn('rol', ['gerente', 'subgerente'])
                    ->first();

                $data = [
                    'id_socio' => $id->id,
                    'nombre_completo' => $perfil->nombre_completo,
                    'num_accion' => null,
                    'tipo_socio' => $id->rol,
                    'estatus_cuenta' => $perfil->estatus
                ];
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

    public function update(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado o token inválido'
            ], 401);
        }

        if ($usuario->rol !== 'socio_titular') {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:M,F,OTRO',
        ]);

        $socio = SocioTitular::find($usuario->user_id);

        if ($socio) {
            $socio->update($validated);
            return response()->json(['success' => true, 'message' => 'Perfil actualizado correctamente']);
        }

        return response()->json(['success' => false, 'message' => 'Error al actualizar'], 500);
    }
}
