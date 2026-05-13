<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * SDH-1101: Iniciar sesión y emitir token
     */
    public function login(Request $request)
    {
        // 1. Validar el payload
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // 2. Buscar al usuario en la tabla central SSO
        $user = User::where('email', $request->email)
            ->select('id', 'email', 'password', 'rol', 'user_id')
            ->first();

        //SDH-247: Bloquear cuentas de gerentes y subgerentes deshabilitadas
        if (in_array($user->rol, ['gerente', 'subgerente']) && $user->activo === false) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta ha sido deshabilitada. Contacta al administrador del sistema.'
            ], 403);
        }

        // 3. Validar existencia y contraseña (Hash Bcrypt)
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // 3.5 Verificar estatus si es instructor
        if ($user->rol === 'instructor') {
            $instructor = $user->instructor;
            if ($instructor && $instructor->estatus === 'INACTIVO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está inactiva. Contacta al administrador.'
                ], 403);
            }
        }

        // 4. Generar token de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Estructura de respuesta exitosa
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->email, // Fallback temporal hasta conectar relaciones con perfil_id
                    'rol' => $user->rol,       // Retorna 'gerente', 'socio_titular', etc.
                ]
            ]
        ], 200);
    }

    /**
     * SDH-1102: Revocar token (Cerrar sesión)
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if ($token) {
            $hash = hash('sha256', $token);
            cache()->forget('sanctum_token_' . $hash);

            \Laravel\Sanctum\PersonalAccessToken::where('token', $hash)->delete();
        }

        return response()->noContent();
    }
}