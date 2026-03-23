<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        /* validation email */
        $correo_table = DB::table('users')
            ->where('email', $request->correo_electronico)
            ->exists();

        if ($correo_table == false) {

            return response()->json([
                "success" => false,
                "message" => "El correo electrónico no pertenece a ningún usuario"
            ]);
        }

        /* hash token */
        $hashedToken = hash('sha256', $request->token);

        /* validation token */
        $token_table = DB::table('password_reset_tokens')
            ->where('email', $request->correo_electronico)
            ->where('token', $hashedToken)
            ->exists();

        if ($token_table == false) {

            return response()->json([
                "success" => false,
                "message" => "El token no es valido"
            ]);
        }

        /* validation date */
        $token_table_date = DB::table('password_reset_tokens')
            ->where('email', $request->correo_electronico)
            ->where('token', $hashedToken)
            ->where('created_at', '>=', now()->subDays(2))
            ->exists();

        if ($token_table_date == false) {

            return response()->json([
                "success" => false,
                "message" => "El token ha expirado"
            ]);
        }

        /* validation password */
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        /* hash password */
        $hashedPassword = Hash::make($request->password);

        /* update password */
        DB::table('users')
            ->where('email', $request->correo_electronico)
            ->update([
            'password' => $hashedPassword,
        ]);

        /* delete token */
        DB::table('password_reset_tokens')
            ->where('email', $request->correo_electronico)
            ->delete();

        return response()->json([
            "success" => true,
            "message" => "Contraseña restablecida exitosamente."
        ]);
    }
}