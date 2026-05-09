<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use Illuminate\Support\Facades\Validator;
use App\Models\SesionActiva;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\SocioTitular;
use App\Http\Controllers\Sanciones;

class ReservacionController extends Controller
{
    // 1. CREAR RESERVA (Paso 3 del Front)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha_reserva' => 'required|date|after_or_equal:today',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'id_espacio' => 'required|integer|exists:espacios_fisicos,id_espacio',
                'id_disciplina' => 'required|integer|exists:disciplinas,id_disciplina',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $espacioActivo = EspacioFisico::where('id_espacio', $request->id_espacio)->where('estatus', 'ACTIVO')->exists();
        if (!$espacioActivo) {
            return response()->json(['success' => false, 'message' => 'El espacio no está disponible'], 400);
        }

        $disciplinaActiva = Disciplina::where('id_disciplina', $request->id_disciplina)->exists();
        if (!$disciplinaActiva) {
            return response()->json(['success' => false, 'message' => 'La disciplina no está disponible'], 400);
        }

        $inicio = Carbon::parse($request->hora_inicio);
        $fin = Carbon::parse($request->hora_fin);

        if ($inicio->diffInMinutes($fin) > 120) {
            return response()->json(['success' => false, 'message' => 'La reservación no puede exceder las 2 horas.'], 400);
        }

        // VALIDAR PENALIZACIÓN DE RESERVAS
        $user = $request->user();
        if ($user->rol === 'socio_titular') {
            $socio = SocioTitular::find($user->user_id);
            if ($socio) {
                $bloqueadoPorEstatus = in_array($socio->estatus_penalizacion, ['PENALIZADO_RESERVA', 'PENALIZADO_AMBOS', 'SUSPENDIDO']);
                $fechaActiva = $socio->estatus_penalizacion !== 'SUSPENDIDO'
                    && $socio->fecha_fin_penalizacion_reserva
                    && $socio->fecha_fin_penalizacion_reserva->isFuture();

                if ($bloqueadoPorEstatus && ($socio->estatus_penalizacion === 'SUSPENDIDO' || $fechaActiva)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tu cuenta tiene una penalización activa en Reservaciones. No puedes realizar nuevas reservas hasta que expire la sanción.',
                        'fecha_liberacion' => $socio->fecha_fin_penalizacion_reserva?->toDateTimeString(),
                    ], 403);
                }
            }
        }

        return DB::transaction(function () use ($request) {

            $id_socio = $request->user()->user_id;

            // 1. VALIDAR EMPALMES CON OTRAS RESERVACIONES (Forzando Timezone de México)
            $ahoraMexico = Carbon::now('America/Mexico_City');

            $conflictoReserva = Reservacion::where('id_espacio', $request->id_espacio)
                ->where('fecha_reserva', $request->fecha_reserva)
                ->where(function ($q) use ($id_socio, $ahoraMexico) {
                    $q->where('estatus_operativo', 'ACTIVA')
                        ->orWhere(function ($sub) use ($id_socio, $ahoraMexico) {
                            $sub->where('estatus_operativo', 'PENDIENTE')
                                ->where('fecha_expiracion', '>', $ahoraMexico) // Corrección Timezone
                                ->where('id_socio_titular', '!=', $id_socio);
                        });
                })
                ->where(function ($query) use ($request) {
                    $query->where('hora_inicio', '<', $request->hora_fin)
                        ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            // 2. VALIDAR EMPALMES CON CLASES/SESIONES ACTIVAS
            $conflictoSesion = SesionActiva::where('fecha_sesion', $request->fecha_reserva)
                ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
                ->whereHas('actividadPlantilla', function ($query) use ($request) {
                    $query->where('id_espacio', $request->id_espacio)
                        ->where('hora_inicio', '<', $request->hora_fin)
                        ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            if ($conflictoReserva || $conflictoSesion) {
                return response()->json(['success' => false, 'message' => 'Espacio agotado. Ya existe una actividad en este horario'], 409);
            }

            // SI TODO ESTÁ LIBRE, CREAMOS LA RESERVA
            $nuevaReserva = Reservacion::updateOrCreate(
                [
                    'id_socio_titular' => $id_socio,
                    'estatus_operativo' => 'PENDIENTE'
                ],
                [
                    'id_espacio' => $request->id_espacio,
                    'id_disciplina' => $request->id_disciplina,
                    'fecha_reserva' => $request->fecha_reserva,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    // BLINDAMOS LA HORA DE CREACIÓN EXACTA A MÉXICO:
                    'fecha_expiracion' => Carbon::now('America/Mexico_City')->addMinutes(15),
                    'estatus_operativo' => 'PENDIENTE'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservación agregada correctamente',
                'id_reserva' => $nuevaReserva->id_reserva
            ]);
        });
    }

    // SINCRONIZAR ACOMPAÑANTES EN EL BORRADOR (JSONB)
    public function syncAcompanantesDraft(Request $request, $id)
    {
        $request->validate([
            'acompanantes' => 'array'
        ]);

        $reserva = Reservacion::where('id_reserva', $id)->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'Reservación no encontrada'], 404);
        }

        if ($reserva->id_socio_titular !== $request->user()->user_id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para modificar este borrador'], 403);
        }

        if ($reserva->estatus_operativo !== 'PENDIENTE') {
            return response()->json(['success' => false, 'message' => 'La reservación ya no es un borrador (estatus: ' . $reserva->estatus_operativo . ')'], 400);
        }

        // Guardamos los acompañantes en la columna JSONB
        $reserva->acompanantes_draft = $request->acompanantes ?? [];
        $reserva->save();

        return response()->json([
            'success' => true,
            'message' => 'Acompañantes del borrador sincronizados'
        ], 200);
    }

    // 2. CONFIRMAR RESERVA (Step 5 del Front)
    public function confirm(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        $reserva = Reservacion::with([
            'espacioFisico:id_espacio,nombre_espacio,capacidad_maxima',
            'disciplina:id_disciplina,nombre_disciplina',
        ])->where('id_reserva', $id)->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'No se encontró la reservación'], 404);
        }

        // Protección contra doble envío
        if ($reserva->estatus_operativo === 'ACTIVA' || $reserva->estatus_operativo === 'CONFIRMADA') {
            return response()->json(['success' => false, 'message' => 'Esta reservación ya fue confirmada previamente.'], 409);
        }

        if ($reserva->estatus_operativo !== 'PENDIENTE') {
            return response()->json(['success' => false, 'message' => 'La reservación no está en estado PENDIENTE.'], 400);
        }

        // Validar expiración asegurando la zona horaria correcta
        $ahoraMexico = Carbon::now('America/Mexico_City');
        if ($reserva->fecha_expiracion && $reserva->fecha_expiracion < $ahoraMexico) {
            return response()->json(['success' => false, 'message' => 'La reservación ha expirado por inactividad. Por favor, crea una nueva.'], 400);
        }

        // Verificar propiedad
        if ($reserva->id_socio_titular !== $request->user()->user_id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para confirmar esta reservación.'], 403);
        }

        // VALIDAR PENALIZACIÓN AL CONFIRMAR
        $user = $request->user();
        if ($user->rol === 'socio_titular') {
            $socio = SocioTitular::find($user->user_id);
            if ($socio) {
                $bloqueadoPorEstatus = in_array($socio->estatus_penalizacion, ['PENALIZADO_RESERVA', 'PENALIZADO_AMBOS', 'SUSPENDIDO']);
                $fechaActiva = $socio->estatus_penalizacion !== 'SUSPENDIDO'
                    && $socio->fecha_fin_penalizacion_reserva
                    && $socio->fecha_fin_penalizacion_reserva->isFuture();

                if ($bloqueadoPorEstatus && ($socio->estatus_penalizacion === 'SUSPENDIDO' || $fechaActiva)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tu cuenta tiene una penalización activa en Reservaciones. No puedes confirmar reservas hasta que expire la sanción.',
                        'fecha_liberacion' => $socio->fecha_fin_penalizacion_reserva?->toDateTimeString(),
                    ], 403);
                }
            }
        }

        try {
            $datosCorreo = null;

            $resultado = DB::transaction(function () use ($request, $reserva, &$datosCorreo) {
                // Leer acompañantes del draft almacenado en BD (columna JSONB)
                $draft = $reserva->acompanantes_draft;


                if (is_string($draft)) {
                    $draft = json_decode($draft, true) ?? [];
                }


                if (!is_array($draft)) {
                    $draft = [];
                }

                // Validar capacidad del espacio
                $espacio = EspacioFisico::where('id_espacio', $reserva->id_espacio)->first();

                if ($espacio && $espacio->capacidad_maxima) {
                    $totalAsistentes = count($draft) + 1; // +1 por el titular

                    if ($totalAsistentes > $espacio->capacidad_maxima) {
                        return response()->json([
                            'success' => false,
                            'message' => "La cancha tiene capacidad para {$espacioCargado->capacidad_maxima} personas. Tienes " . count($draft) . " acompañantes + tú = {$totalAsistentes}."
                        ], 422);
                    }
                }

                // Cambiar estatus a ACTIVA y limpiar datos temporales de expiración
                $reserva->estatus_operativo = 'ACTIVA';
                $reserva->fecha_expiracion  = null;
                $reserva->save();

                // Enviar correo de confirmación
                try {
                    $user = $reserva->id_socio_titular == $request->user()->user_id ? $request->user() : \App\Models\User::find($reserva->id_socio_titular);
                    if ($user && $user->email) {
                        $disciplina = $reserva->disciplina->nombre_disciplina ?? 'Deporte';
                        $espacio = $reserva->espacioFisico->nombre_espacio ?? 'Espacio';
                        $hora = $reserva->hora_inicio . ' - ' . $reserva->hora_fin;
                        $fecha = $reserva->fecha_reserva;

                        Mail::raw(
                            "Estimado(a) {$user->nombre_completo},

                                Nos complace informarte que tu reservación ha sido confirmada correctamente.
                                                                
                                Detalles de la reservación:

                                • Disciplina: {$disciplina}
                                • Espacio reservado: {$espacio}
                                • Fecha: {$fecha}
                                • Horario: {$hora}

                                Por favor, procura llegar con anticipación para disfrutar de tu reservación
                                sin inconvenientes.

                                Agradecemos tu preferencia.

                                Atentamente,
                                SOC-DEP HUB",
                            function ($message) use ($user) {

                                $message->to($user->email)
                                    ->subject('Confirmación de Reservación | SOC-DEP HUB');
                            }
                        );
                    }
                } catch (\Exception $mailEx) {
                    Log::error("Error al enviar correo de confirmación: " . $mailEx->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Reservación confirmada exitosamente'
                ]);
            });

            // Envío del correo FUERA de la transacción — no bloquea la BD
            if ($datosCorreo) {
                try {
                    Mail::raw(
                        "Tu reservación para {$datosCorreo['disciplina']} en {$datosCorreo['espacio']} " .
                        "ha sido confirmada para el día {$datosCorreo['fecha']} en el horario {$datosCorreo['hora']}.",
                        fn($m) => $m->to($datosCorreo['email'])->subject('Confirmación de Reservación - SOC-DEP HUB')
                    );
                } catch (\Exception $mailEx) {
                    Log::error("Error al enviar correo de confirmación: " . $mailEx->getMessage());
                }
            }

            return $resultado;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la confirmación: ' . $e->getMessage()
            ], 500);
        }
    }

    // 3. CANCELAR RESERVA ACTIVA (Si el usuario se sale antes de tiempo)
    public function cancel(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        $reservacion = Reservacion::where('id_reserva', $id)
            ->where('id_socio_titular', $request->user()->user_id)
            ->first();

        if (!$reservacion) {
            return response()->json(['success' => false, 'message' => 'Reservación no encontrada'], 404);
        }

        // Solo se pueden cancelar reservaciones ACTIVAS
        if ($reservacion->estatus_operativo !== 'ACTIVA') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden cancelar reservaciones activas'], 400);
        }

        // Determinar si aplica NO_SHOW según tiempo restante (menos de 2 horas o ya pasada)
        $now = Carbon::now('America/Mexico_City');
        $fechaHoraReserva = Carbon::parse(
            $reservacion->fecha_reserva . ' ' . $reservacion->hora_inicio,
            'America/Mexico_City'
        );

        $minutosRestantes = $now->diffInMinutes($fechaHoraReserva, false);

        // Si faltan menos de 120 minutos (2 horas) es NO_SHOW. 
        // Si minutosRestantes es negativo, significa que la reserva ya pasó/inició, también es NO_SHOW.
        $nuevoEstatus = ($minutosRestantes < 120) ? 'NO_SHOW' : 'CANCELADA';

        $reservacion->estatus_operativo = $nuevoEstatus;
        $reservacion->save();

        // Si es NO_SHOW, incrementar el contador y delegar la sanción a Sanciones
        if ($nuevoEstatus === 'NO_SHOW') {
            $socio = SocioTitular::find($reservacion->id_socio_titular);
            if ($socio) {
                $socio->increment('contador_no_shows');
                Sanciones::aplicarSancionesReservas($socio->id_socio);
            }
        }

        return response()->json([
            'success' => true,
            'nuevo_estatus' => $nuevoEstatus,
            'message' => $nuevoEstatus === 'NO_SHOW'
                ? 'Reservación cancelada tardíamente. Se registró un No Show en tu cuenta.'
                : 'Reservación cancelada correctamente. El espacio ha sido liberado.'
        ]);
    }

    // 4. DESCARTAR BORRADOR (Eliminación permanente de DB)
    public function discard(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        $reservacion = Reservacion::where('id_reserva', $id)
            ->where('id_socio_titular', $request->user()->user_id)
            ->where('estatus_operativo', 'PENDIENTE')
            ->first();

        if (!$reservacion) {
            return response()->json(['success' => false, 'message' => 'Borrador no encontrado'], 404);
        }

        // Eliminación permanente de la base de datos
        $reservacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Borrador descartado y eliminado permanentemente de la base de datos.'
        ]);
    }

    public function getActiveDraft(Request $request)
    {
        $id_socio = $request->user()->user_id;
        $ahoraMexico = Carbon::now('America/Mexico_City');

        $reserva = Reservacion::where('id_socio_titular', $id_socio)
            ->where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '>', $ahoraMexico) // Corrección Timezone
            ->with([
                'espacioFisico:id_espacio,nombre_espacio',
                'disciplina:id_disciplina,nombre_disciplina'
            ])
            ->first();

        return response()->json([
            'success' => !!$reserva,
            'reserva' => $reserva
        ]);
    }

    public function myReservations(Request $request)
    {
        $socioId = $request->user()->user_id;
        $limit = $request->query('limit', 20);
        $ahoraMexico = Carbon::now('America/Mexico_City');

        $query = Reservacion::where('id_socio_titular', $socioId)
            ->with(['espacioFisico:id_espacio,nombre_espacio', 'disciplina:id_disciplina,nombre_disciplina'])
            ->orderBy('fecha_reserva', 'desc')
            ->orderBy('hora_inicio', 'desc');

        // Omitimos SOLO los borradores que ya expiraron (Timezone correcto)
        $query->where(function ($q) use ($ahoraMexico) {
            $q->where('estatus_operativo', '!=', 'PENDIENTE')
                ->orWhere('fecha_expiracion', '>', $ahoraMexico);
        });

        $reservas = $query->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $reservas
        ]);
    }
}