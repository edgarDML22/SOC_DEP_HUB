<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use App\Models\SocioTitular;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class GuestPassController extends Controller
{
    public function store(Request $request)
    {
        /* Validar datos */
        $request->validate([
            'id' => 'required|integer',
            'nombre_invitado' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|digits_between:10,15'
        ]);

        /* Validar socio */
        $id_valido = SocioTitular::where('id_socio', $request->id)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);
        }

        /* Validar correo duplicado */
        $corre_validacion = Invitados::where('correo', $request->correo)
            ->where('socio_id', $request->id)
            ->first();

        if ($corre_validacion != null) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un invitado con ese correo'
            ], 400);
        }

        /* Validar límite */
        $count = SocioTitular::find($request->id)
            ->invitados()
            ->whereHas('pase', function ($query) {
                $query->where('estatus_acceso', 'ACTIVO');
            })
            ->count();

        if ($count >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Has alcanzado el límite máximo de 5 invitados activos simultáneamente.'
            ], 400);
        }

        /* Generar código QR */
        do {
            $codigoQR = 'QI' . substr(str_replace('-', '', Str::uuid()), 0, 6);
        } while (Invitados::where('codigo_qr', $codigoQR)->exists());

        /* Insertar invitado */
        $insertar = Invitados::create([
            'socio_id' => $request->id,
            'nombre_invitado' => $request->nombre_invitado,
            'codigo_qr' => $codigoQR,
            'correo' => $request->correo,
            'telefono' => $request->telefono
        ]);

        /* Insertar pase */
        $insertar_pase = PasesDiarios::create([
            'invitado_id' => $insertar->id_invitado,
            'estatus_acceso' => 'EXPIRADO',
            'fecha_activacion' => now(),
        ]);

        /* Validar inserción */
        if (!$insertar || !$insertar_pase) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar invitado'
            ], 500);
        }

        /* Generar contenido del QR (JSON) */
        $data = json_encode([
            'codigo_qr' => $codigoQR,
            'tipo' => 'invitado'
        ]);

        /* Generar imagen QR (API externa) */
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

        /* Enviar correo */
        if ($request->correo) {
            try {
                Mail::send([], [], function ($message) use ($request, $qrUrl, $codigoQR) {
                    $message->to($request->correo)
                        ->subject('Tu acceso como invitado')
                        ->html("
                            <div style='text-align:center; font-family:Arial'>
                                <h2>Hola {$request->nombre_invitado}</h2>
                                <p>Presenta este código QR en la entrada:</p>
                                <div style='margin:20px 0'>
                                    <img src='$qrUrl' alt='QR Code' />
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Log::error('Error enviando correo: ' . $e->getMessage());
            }
        }

        /* Respuesta */
        return response()->json([
            'id_invitado' => $insertar->id_invitado,
            'socio_id' => $request->id,
            'nombre_invitado' => $request->nombre_invitado,
            'codigo_qr' => $codigoQR,
            'estatus_acceso' => $insertar_pase->estatus_acceso,
        ], 201);
    }
}