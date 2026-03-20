<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {

        /* Revisamos si el correo es valido */
        $request->validate([
            'correo_electronico' => 'required|email'
        ]);

        /* Hacemos consulta que el correo existe en la base de datos*/
        $correo_table = DB::table('socios_titulares')
            ->where('correo_electronico', $request->correo_electronico)
            ->exists();

        /* Si es falso retornamos */
        if ($correo_table == false) {

            return response()->json([
                "success" => false,
                "message" => "El correo electrónico no pertenece a ningún socio"
            ]);
        }

        /* Genera el token y envia el correo */
        $token = Str::random(60);
        $hashedToken = hash('sha256', $token);
        DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->correo_electronico],
        [
            'token' => $hashedToken,
            'created_at' => now()
        ]
        );
        /* Genera link */
        $link = "http://localhost:5173/reset-password?token=$token&email=" . $request->correo_electronico;

        /* Envía correo */
        Mail::raw("Recupera tu contraseña aquí: $link", function ($message) use ($request) {
            $message->to($request->correo_electronico)
                ->subject('Recuperación de contraseña');
        });

        return response()->json([
            "success" => true,
            "message" => "Enlace de recuperación enviado al correo electrónico."
        ]);


    }
}