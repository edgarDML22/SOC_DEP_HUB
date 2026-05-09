<?php

namespace App\Http\Controllers;

use App\Models\Invitados;
use App\Models\PasesDiarios;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestStatusController extends Controller
{
    // MÉTODO 1: OBTENER LISTA DE INVITADOS
    public function show(Request $request)
    {
        $user = $request->user();
        $socioId = $user->user_id;

        // Si es admin, puede consultar los invitados de cualquier socio vía query param
        if (in_array($user->rol, ['gerente', 'subgerente']) && $request->has('socio_id')) {
            $socioId = $request->query('socio_id');
        }

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
        /* 1. Obtenemos el ID del Socio */
        $user = $request->user();
        $socioId = $user->user_id;

        // Si es admin, puede registrar invitados para cualquier socio
        if (in_array($user->rol, ['gerente', 'subgerente']) && $request->has('socio_id')) {
            $socioId = $request->input('socio_id');
        }


        /* 2. Validar datos */
        $request->validate([
            'nombre_invitado' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|digits_between:10,15',
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

        /* 5. Validar límite de 5 pases activos */
        $countActivos = SocioTitular::find($socioId)
            ->invitados()
            ->whereHas('pase', function ($query) {
                $query->where('estatus_acceso', 'ACTIVO');
            })
            ->count();

        if ($countActivos >= 5) {
            return response()->json(['success' => false, 'message' => 'Has alcanzado el límite máximo de 5 pases activos.'], 400);
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
                    'estatus_acceso' => 'ACTIVO',
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
                'id_invitado' => $insertar->id_invitado,
                'socio_id' => $socioId,
                'nombre_invitado' => $request->nombre_invitado,
                'codigo_qr' => $codigoQR,
                'estatus_acceso' => $insertar_pase->estatus_acceso,
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
        $user = $request->user();
        $socioId = $user->user_id;
        $isAdmin = in_array($user->rol, ['gerente', 'subgerente']);

        // 1. Buscamos al invitado
        $query = Invitados::where('id_invitado', $id);

        // Si no es admin, solo puede editar sus propios invitados
        if (!$isAdmin) {
            $query->where('socio_id', $socioId);
        }

        $invitado = $query->first();

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
        // El admin puede pasar socio_id, el socio usa su propio id
        $socioId = $request->input('socio_id');
        $isAdmin = $request->user()->rol === 'gerente' || $request->user()->rol === 'subgerente';

        if (!$socioId || !$isAdmin) {
            $socioId = $request->user()->user_id;
        }

        $invitado = Invitados::where('id_invitado', $id)
            ->where('socio_id', $socioId)
            ->first();

        if (!$invitado) {
            return response()->json(['success' => false, 'message' => 'Invitado no encontrado.'], 404);
        }

        // Lógica de eliminación: Hard vs Soft
        // Umbral de 20 minutos
        $isNew = $invitado->created_at ? $invitado->created_at->diffInMinutes(now()) < 20 : true;

        if ($isNew) {
            // HARD DELETE: Eliminar físicamente
            try {
                DB::transaction(function () use ($invitado) {
                    if ($invitado->pase) {
                        $invitado->pase->delete();
                    }
                    $invitado->forceDelete(); // forceDelete para asegurar que se va de la DB
                });
                return response()->json(['success' => true, 'message' => 'Invitado eliminado permanentemente.']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error al eliminar el invitado.'], 500);
            }
        } else {
            // SOFT DELETE: Validar que no tenga reservas activas
            // 1. Verificar en reservaciones_on_demand (acompanantes_draft)
            $hasReservations = Reservacion::where('estatus_operativo', '!=', 'CANCELADA')
                ->where(function ($query) use ($invitado) {
                    // Buscamos el nombre o ID en el JSON de acompañantes
                    $query->whereJsonContains('acompanantes_draft', ['id_invitado' => $invitado->id_invitado])
                        ->orWhereJsonContains('acompanantes_draft', ['nombre' => $invitado->nombre_invitado]);
                })->exists();

            if ($hasReservations) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el invitado porque tiene reservaciones activas.'
                ], 400);
            }

            try {
                DB::transaction(function () use ($invitado) {
                    // Desactivar pase
                    if ($invitado->pase) {
                        $invitado->pase->update(['estatus_acceso' => 'EXPIRADO']);
                    }
                    // Soft delete
                    $invitado->delete();
                });
                return response()->json(['success' => true, 'message' => 'Invitado desactivado correctamente.']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error al desactivar el invitado.'], 500);
            }
        }
    }
    public function togglePass(Request $request, $invitadoId)
    {
        $user = $request->user();
        $socioId = $user->user_id;
        $isAdmin = in_array($user->rol, ['gerente', 'subgerente']);

        // 1. Buscamos al invitado
        $query = Invitados::where('id_invitado', $invitadoId);

        // Si no es admin, solo puede gestionar sus propios invitados
        if (!$isAdmin) {
            $query->where('socio_id', $socioId);
        }

        $invitado = $query->first();

        if (!$invitado) {
            return response()->json(['success' => false, 'message' => 'Invitado no encontrado o no autorizado'], 404);
        }

        // 2. Traemos el pase diario y actualizamos el estatus
        $pase = PasesDiarios::where('invitado_id', $invitadoId)
            ->first();

        if (!$pase) {
            return response()->json(['success' => false, 'message' => 'Pase no encontrado'], 404);
        }

        // 3. Actualizamos el estatus
        $nuevoEstatus = $pase->estatus_acceso === 'ACTIVO' ? 'EXPIRADO' : 'ACTIVO';

        // Validar límite si se va a activar
        if ($nuevoEstatus === 'ACTIVO') {
            $countActivos = Invitados::where('socio_id', $invitado->socio_id)
                ->whereHas('pase', function ($query) {
                    $query->where('estatus_acceso', 'ACTIVO');
                })
                ->count();

            if ($countActivos >= 5) {
                return response()->json(['success' => false, 'message' => 'Límite máximo de 5 pases activos alcanzado.'], 400);
            }
            
            // Renovar fechas al activar
            $pase->update([
                'estatus_acceso' => $nuevoEstatus,
                'fecha_activacion' => now(),
                'fecha_expiracion' => now()->addDay(),
            ]);
        } else {
            $pase->update([
                'estatus_acceso' => $nuevoEstatus,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pase actualizado',
            'data' => $pase
        ], 200);

    }


}