<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Gerentes;

class UserAdminController extends Controller
{
    //SDH-248:CRUD
    public function index()
    {
        $users = User::query()
            ->join(
                'gerentes',
                'users.user_id',
                '=',
                'gerentes.id_empleado'
            )
            ->where('users.rol', 'subgerente')
            ->select(
                'users.id',
                'gerentes.id_empleado',
                'gerentes.nombre_completo as name',
                'users.email',
                'gerentes.cargo',
                'users.rol',
                'users.activo'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request) //add gerente
    {
        $request->validate([
            'nombre_completo' => 'required|string',
            'correo_electronico' => 'required|email',
            'password' => 'required|min:8|string',
            'cargo' => 'required|string',
            'rol' => 'required|string',
        ]);
        $gerente = Gerentes::create([
            'nombre_completo' => $request->nombre_completo,
            'correo_electronico' => $request->correo_electronico,
            'cargo' => $request->cargo,
        ]);

        $user = User::create([
            'user_id' => $gerente->id_empleado,
            'email' => $request->correo_electronico,
            'rol' => $request->rol,
            'password' => Hash::make($request->password),
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Gerente creado exitosamente',
            'data' => $gerente,
            'user' => $user
        ], 201);
    }

    public function toggleActivo(Request $request, $id)
    {
        //SDH-248: Validar que el usuario que desea deshabilitar un usuario no sea subgerente
        if (auth()->user()->rol !== 'gerente') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }
        $request->validate([
            'activo' => 'required|boolean',
        ]);
        $user = User::find($id);
        $user->update([
            'activo' => $request->activo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => [
                'id' => $user->id,
                'activo' => $request->activo
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_empleado' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'cargo' => 'required|string',
            'password' => 'nullable|min:8|string',
            'rol' => 'nullable|string|in:gerente,subgerente',
        ]);

        $user = User::findOrFail($id);

        $userData = [
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->filled('rol')) {
            $userData['rol'] = $request->rol;
        }

        $user->update($userData);

        $gerente = Gerentes::findOrFail($user->user_id);

        $gerente->update([
            'nombre_completo' => $request->nombre_empleado,
            'correo_electronico' => $request->email,
            'cargo' => $request->cargo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente'
        ]);
    }
}