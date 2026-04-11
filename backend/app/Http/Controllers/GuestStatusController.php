<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use App\Models\SocioTitular;
use Exception;
// use App\Models\PasesDiarios; // Descomenta si lo usas
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestStatusController extends Controller
{
    // MÉTODO 1: OBTENER LISTA DE INVITADOS
    public function show(Request $request)
    {
        $socioId = $request->user()->user_id;

        // 2. Traemos todos sus invitados con sus respectivos pases
        $invitados = Invitados::with('pase')->where('socio_id', $socioId)->get();

        // 3. Formateamos la data para el Front
        return response()->json([
            'success' => true,
            'data' => $invitados->map(function ($inv) {
                $pase = $inv->pase;
                return [
                    'id' => $inv->id_invitado,
                    'nombre' => $inv->nombre_invitado,
                    'codigo_qr' => $inv->codigo_qr,
                    'correo' => $inv->correo,
                    'telefono' => $inv->telefono,
                    'estatus_acceso' => $pase?->estatus_acceso ?? 'SIN_PASE',
                    'fecha_expiracion' => $pase?->fecha_expiracion,
                    'id_pase' => $pase?->id_pase,
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        /* 1. Obtenemos el ID del Socio desde el Token (Súper seguro) */
        $socioId = $request->user()->user_id;

        /* 2. Validar datos (Ya quitamos el 'id' porque lo sacamos del token) */
        $request->validate([
            'nombre_invitado' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|digits_between:10,15'
        ]);

        /* 3. Validar socio */
        $id_valido = SocioTitular::where('id_socio', $socioId)->first();
        if ($id_valido == null) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro socio con ese id'
            ], 404);
        }

        /* 4. Validar correo duplicado */
        $corre_validacion = Invitados::where('correo', $request->correo)
            ->where('socio_id', $socioId)
            ->first();

        if ($corre_validacion != null) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un invitado con ese correo'
            ], 400);
        }

        /* 5. Validar límite (Usando el ID del token) */
        $count = SocioTitular::find($socioId)
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

        /* 6. Generar código QR */
        do {
            $codigoQR = 'QI' . substr(str_replace('-', '', Str::uuid()), 0, 6);
        } while (Invitados::where('codigo_qr', $codigoQR)->exists());

        /* 7. Insertar invitado (Con el ID del token) */
        $insertar = Invitados::create([
            'socio_id' => $socioId,
            'nombre_invitado' => $request->nombre_invitado,
            'codigo_qr' => $codigoQR,
            'correo' => $request->correo,
            'telefono' => $request->telefono
        ]);

        /* 8. Insertar pase */
        $insertar_pase = PasesDiarios::create([
            'invitado_id' => $insertar->id_invitado,
            'estatus_acceso' => 'EXPIRADO',
            'fecha_activacion' => now(),
        ]);

        /* 9. Validar inserción */
        if (!$insertar || !$insertar_pase) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar invitado'
            ], 500);
        }

        /* 10. Generar contenido del QR (JSON) */
        $data = json_encode([
            'codigo_qr' => $codigoQR,
            'tipo' => 'invitado'
        ]);

        /* 11. Generar imagen QR (API externa) */
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

        /* 12. Enviar correo */
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
            } catch (Exception $e) {
                // Aquí ya no marcará error porque importamos Log y Exception arriba
                Log::error('Error enviando correo: ' . $e->getMessage());
            }
        }

        /* 13. Respuesta exacta a la que tenían (con la data para el Frontend) */
        return response()->json([
            'id_invitado' => $insertar->id_invitado,
            'socio_id' => $socioId,
            'nombre_invitado' => $request->nombre_invitado,
            'codigo_qr' => $codigoQR,
            'estatus_acceso' => $insertar_pase->estatus_acceso,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $socioId = $request->user()->user_id;

        // 1. Buscamos al invitado asegurándonos de que pertenezca a este socio
        $invitado = Invitados::where('id_invitado', $id)
            ->where('socio_id', $socioId)
            ->first();

        if (!$invitado) {
            return response()->json(['success' => false, 'message' => 'Invitado no encontrado o no autorizado'], 404);
        }

        // 2. Validamos los datos nuevos
        $request->validate([
            'nombre_invitado' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20'
        ]);

        // 3. Actualizamos
        $invitado->update([
            'nombre_invitado' => $request->nombre_invitado,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitado actualizado',
            'data' => $invitado
        ], 200);
    }

    public function destroy(Request $request, $id)
    {

        $socioId = $request->user()->user_id;

        $invitado = Invitados::where('id_invitado', $id)
            ->where('socio_id', $socioId)
            ->first();

        if (!$invitado) {
            return response()->json(['success' => false, 'message' => 'Invitado no encontrado o no autorizado'], 404);
        }

        // Al eliminar al invitado, asegúrate de que tu base de datos tenga eliminación 
        // en cascada para los pases_diarios, o elimínalo manualmente aquí si es necesario.
        $invitado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invitado eliminado correctamente'
        ], 200);
    }
}