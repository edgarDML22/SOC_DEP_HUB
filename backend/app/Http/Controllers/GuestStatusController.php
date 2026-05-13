<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use App\Models\SocioTitular;
use Exception;
// use App\Models\PasesDiarios; // Descomenta si lo usas
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestStatusController extends Controller
{
    // MÉTODO 1: OBTENER LISTA DE INVITADOS
    public function show(Request $request)
    {
        $socioId = $request->user()->user_id;

        $invitados = Invitados::with(['pase:id_pase,invitado_id,estatus_acceso,fecha_expiracion'])
            ->select(['id_invitado', 'socio_id', 'nombre_invitado', 'codigo_qr', 'correo', 'telefono'])
            ->where('socio_id', $socioId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitados->map(function ($inv) {
                $pase = $inv->pase;
                return [
                    'id'               => $inv->id_invitado,
                    'nombre'           => $inv->nombre_invitado,
                    'codigo_qr'        => $inv->codigo_qr,
                    'correo'           => $inv->correo,
                    'telefono'         => $inv->telefono,
                    'estatus_acceso'   => $pase?->estatus_acceso ?? 'SIN_PASE',
                    'fecha_expiracion' => $pase?->fecha_expiracion,
                    'id_pase'          => $pase?->id_pase,
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        /* 1. Obtenemos el ID del Socio desde el Token */
        $socioId = $request->user()->user_id;

        /* 2. Validar datos */
        $request->validate([
            'nombre_invitado' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|digits_between:10,15'
        ]);

        /* 3. Validar socio */
        $id_valido = SocioTitular::where('id_socio', $socioId)->first();
        if (!$id_valido) {
            return response()->json(['success' => false, 'message' => 'No se encontró socio con ese id'], 404);
        }

        /* 4. Validar correo duplicado */
        $correo_validacion = Invitados::where('correo', $request->correo)
            ->where('socio_id', $socioId)
            ->first();

        if ($correo_validacion) {
            return response()->json(['success' => false, 'message' => 'Ya existe un invitado con ese correo'], 400);
        }

        /* 5. Validar límite */
        $count = Invitados::where('socio_id', $socioId)
            ->whereHas('pase', function ($query) {
                $query->where('estatus_acceso', 'ACTIVO');
            })
            ->count();

        if ($count >= 8) {
            return response()->json(['success' => false, 'message' => 'Has alcanzado el límite máximo de 8 invitados.'], 400);
        }

        /* 6. Generar código QR */
        do {
            $codigoQR = 'QI' . strtoupper(substr(str_replace('-', '', Str::uuid()), 0, 6));
        } while (Invitados::where('codigo_qr', $codigoQR)->exists());

        try {
            // === INICIA LA TRANSACCIÓN ===
            $result = DB::transaction(function () use ($socioId, $request, $codigoQR) {
                
                /* 7. Insertar invitado */
                $insertar = Invitados::create([
                    'socio_id' => $socioId,
                    'nombre_invitado' => $request->nombre_invitado,
                    'codigo_qr' => $codigoQR,
                    'correo' => $request->correo,
                    'telefono' => $request->telefono
                ]);

                /* 8. Insertar pase asociado al invitado recién creado */
                $insertar_pase = PasesDiarios::create([
                    'invitado_id' => $insertar->id_invitado,
                    'estatus_acceso' => 'EXPIRADO', 
                    'fecha_activacion' => now(),
                ]);

                return [
                    'invitado' => $insertar,
                    'pase' => $insertar_pase
                ];
            });
            // === TERMINA LA TRANSACCIÓN ===

            $insertar = $result['invitado'];
            $insertar_pase = $result['pase'];

            /* 9. Generar contenido del QR (JSON) e imagen */
            $data = json_encode([
                'codigo_qr' => $codigoQR,
                'tipo' => 'invitado'
            ]);
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

            /* 10. Enviar correo (fuera de la transacción para no enviar spam si la DB falla) */
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
                    Log::error('Error enviando correo: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id'               => $insertar->id_invitado,
                    'nombre'           => $insertar->nombre_invitado,
                    'codigo_qr'        => $codigoQR,
                    'correo'           => $insertar->correo,
                    'telefono'         => $insertar->telefono,
                    'estatus_acceso'   => $insertar_pase->estatus_acceso,
                    'fecha_expiracion' => $insertar_pase->fecha_expiracion,
                    'id_pase'          => $insertar_pase->id_pase,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en transacción de Invitado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al agregar invitado. Intenta más tarde.'
            ], 500);
        }
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

        // Traemos al invitado con su relación de pase diario
        $invitado = Invitados::with('pase')
                             ->where('id_invitado', $id)
                             ->where('socio_id', $socioId)
                             ->first();

        if (!$invitado) {
            return response()->json(['success' => false, 'message' => 'Invitado no encontrado o no autorizado'], 404);
        }

        try {
            // === INICIA LA TRANSACCIÓN PARA ELIMINACIÓN EN CASCADA ===
            DB::transaction(function () use ($invitado) {
                
                // 1. Deshabilitar el pase diario (Lo pasamos a EXPIRADO o equivalente)
                if ($invitado->pase) {
                    $invitado->pase->update([
                        'estatus_acceso' => 'EXPIRADO' 
                    ]);
                }

                // 2. Soft delete del invitado (llena el campo deleted_at de forma automática)
                $invitado->delete();
            });
            // === TERMINA LA TRANSACCIÓN ===

            return response()->json([
                'success' => true,
                'message' => 'Invitado eliminado y pase deshabilitado correctamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar invitado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al procesar la eliminación'
            ], 500);
        }
    }


}