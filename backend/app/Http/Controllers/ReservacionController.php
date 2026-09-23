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
use App\Models\EncuentrosTorneo;
use App\Models\InscripcionClase;
use App\Models\ParticipantesTorneo;
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

        $user = $request->user();
        if ($error = $this->validarPenalizacionReservas($user)) {
            return response()->json($error, 403);
        }

        return DB::transaction(function () use ($request) {

            $id_socio = $request->user()->user_id;

            $resultado = $this->verificarEmpalmes(
                $id_socio,
                $request->id_espacio,
                $request->fecha_reserva,
                $request->hora_inicio,
                $request->hora_fin
            );

            if ($resultado !== null) {
                return response()->json(['success' => false, 'message' => $resultado], 409);
            }

            // SI TODO ESTÁ LIBRE, CREAMOS O ACTUALIZAMOS EL BORRADOR (con lock para evitar race condition)
            $borradorExistente = Reservacion::where('id_socio_titular', $id_socio)
                ->where('estatus_operativo', 'PENDIENTE')
                ->lockForUpdate()
                ->first();

            if ($borradorExistente) {
                $borradorExistente->update([
                    'id_espacio'       => $request->id_espacio,
                    'id_disciplina'    => $request->id_disciplina,
                    'fecha_reserva'    => $request->fecha_reserva,
                    'hora_inicio'      => $request->hora_inicio,
                    'hora_fin'         => $request->hora_fin,
                    'fecha_expiracion' => Carbon::now('America/Mexico_City')->addMinutes(15),
                ]);
                $nuevaReserva = $borradorExistente;
            } else {
                $nuevaReserva = Reservacion::create([
                    'id_socio_titular'  => $id_socio,
                    'id_espacio'        => $request->id_espacio,
                    'id_disciplina'     => $request->id_disciplina,
                    'fecha_reserva'     => $request->fecha_reserva,
                    'hora_inicio'       => $request->hora_inicio,
                    'hora_fin'          => $request->hora_fin,
                    'estatus_operativo' => 'PENDIENTE',
                    'fecha_expiracion'  => Carbon::now('America/Mexico_City')->addMinutes(15),
                ]);
            }

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
        if ($error = $this->validarPenalizacionReservas($request->user())) {
            return response()->json($error, 403);
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

                // Validar capacidad del espacio — reutiliza la relación ya eager-loaded
                $espacio = $reserva->espacioFisico;

                if ($espacio && $espacio->capacidad_maxima) {
                    $totalAsistentes = count($draft) + 1; // +1 por el titular

                    if ($totalAsistentes > $espacio->capacidad_maxima) {
                        return response()->json([
                            'success' => false,
                            'message' => "La cancha tiene capacidad para {$espacio->capacidad_maxima} personas. Tienes " . count($draft) . " acompañantes + tú = {$totalAsistentes}."
                        ], 422);
                    }
                }

                // Cambiar estatus a ACTIVA y limpiar datos temporales de expiración
                $reserva->estatus_operativo = 'ACTIVA';
                $reserva->fecha_expiracion  = null;
                $reserva->save();

                // Capturar los datos para el correo DENTRO de la transacción,
                // pero el envío ocurre FUERA para no bloquear la conexión a la BD.
                $user = $reserva->id_socio_titular == $request->user()->user_id
                    ? $request->user()
                    : \App\Models\User::find($reserva->id_socio_titular);

                if ($user && $user->email) {
                    $datosCorreo = [
                        'email'      => $user->email,
                        'nombre'     => $user->nombre_completo ?? $user->email,
                        'disciplina' => $reserva->disciplina->nombre_disciplina ?? 'Deporte',
                        'espacio'    => $reserva->espacioFisico->nombre_espacio ?? 'Espacio',
                        'hora'       => $reserva->hora_inicio . ' - ' . $reserva->hora_fin,
                        'fecha'      => $reserva->fecha_reserva,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Reservación confirmada exitosamente'
                ]);
            });

            // Envío del correo FUERA de la transacción — la conexión a Neon ya fue liberada.
            // Si la transacción falló, $datosCorreo sigue null y no se envía nada.
            if ($datosCorreo) {
                try {
                    Mail::raw(
                        "Estimado(a) {$datosCorreo['nombre']},

Nos complace informarte que tu reservación ha sido confirmada correctamente.

Detalles de la reservación:

• Disciplina: {$datosCorreo['disciplina']}
• Espacio reservado: {$datosCorreo['espacio']}
• Fecha: {$datosCorreo['fecha']}
• Horario: {$datosCorreo['hora']}

Por favor, procura llegar con anticipación para disfrutar de tu reservación
sin inconvenientes.

Agradecemos tu preferencia.

Atentamente,
SOC-DEP HUB",
                        fn($m) => $m->to($datosCorreo['email'])
                                    ->subject('Confirmación de Reservación | SOC-DEP HUB')
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

        $reserva = Reservacion::select([
                'id_reserva', 'id_socio_titular', 'id_espacio', 'id_disciplina',
                'fecha_reserva', 'hora_inicio', 'hora_fin',
                'estatus_operativo', 'fecha_expiracion', 'acompanantes_draft',
            ])
            ->where('id_socio_titular', $id_socio)
            ->where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '>', $ahoraMexico)
            ->with([
                'espacioFisico:id_espacio,nombre_espacio,capacidad_maxima',
                'disciplina:id_disciplina,nombre_disciplina',
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

    /**
     * Verifica los 6 posibles empalmes antes de crear un borrador.
     * Retorna el mensaje de error si hay conflicto, o null si el horario está libre.
     *
     * BLOQUE 1 — Conflictos del espacio (actividades del club):
     *   1a. Reservaciones ACTIVAS o PENDIENTES de otros socios en el mismo espacio/fecha/horario
     *   1b. Sesiones/clases programadas en ese espacio en ese horario
     *   1c. Encuentros de torneo programados en ese espacio en ese horario
     *
     * BLOQUE 2 — Conflictos personales del socio (su propia agenda):
     *   2a. Sus propias reservaciones ACTIVAS o PENDIENTES en cualquier espacio ese día/horario
     *   2b. Sus clases confirmadas para esa fecha/horario
     *   2c. Sus encuentros de torneo para esa fecha/horario
     */
    private function verificarEmpalmes(
        int    $idSocio,
        int    $idEspacio,
        string $fecha,
        string $horaInicio,
        string $horaFin
    ): ?string {
        $ahora       = Carbon::now('America/Mexico_City');
        $fechaHoraFin   = $fecha . ' ' . $horaFin;
        $fechaHoraInicio = $fecha . ' ' . $horaInicio;

        // --- BLOQUE 1: CONFLICTOS DEL ESPACIO ---

        // 1a. Reservaciones de otros socios en este espacio
        $b1Reserva = Reservacion::where('id_espacio', $idEspacio)
            ->where('fecha_reserva', $fecha)
            ->where(function ($q) use ($idSocio, $ahora) {
                $q->where('estatus_operativo', 'ACTIVA')
                    ->orWhere(function ($sub) use ($idSocio, $ahora) {
                        $sub->where('estatus_operativo', 'PENDIENTE')
                            ->where('fecha_expiracion', '>', $ahora)
                            ->where('id_socio_titular', '!=', $idSocio);
                    });
            })
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();

        if ($b1Reserva) {
            return 'Espacio agotado. Ya existe una reservación en este horario.';
        }

        // 1b. Sesiones/clases del club en este espacio
        $b1Sesion = SesionActiva::where('fecha_sesion', $fecha)
            ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
            ->whereHas('actividadPlantilla', function ($q) use ($idEspacio, $horaInicio, $horaFin) {
                $q->where('id_espacio', $idEspacio)
                    ->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();

        if ($b1Sesion) {
            return 'Espacio agotado. El club tiene una sesión programada en este horario.';
        }

        // 1c. Encuentros de torneo del club en este espacio
        $b1Torneo = EncuentrosTorneo::where('id_espacio', $idEspacio)
            ->whereDate('fecha_hora_inicio', $fecha)
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->whereNotNull('fecha_hora_inicio')
            ->whereNotNull('fecha_hora_fin')
            ->where('fecha_hora_inicio', '<', $fechaHoraFin)
            ->where('fecha_hora_fin', '>', $fechaHoraInicio)
            ->exists();

        if ($b1Torneo) {
            return 'Espacio agotado. El club tiene un encuentro de torneo programado en este horario.';
        }

        // --- BLOQUE 2: CONFLICTOS DE LA AGENDA PERSONAL DEL SOCIO ---

        // 2a. Sus propias reservaciones activas/pendientes (cualquier espacio, mismo horario)
        $b2Reserva = Reservacion::where('id_socio_titular', $idSocio)
            ->where('fecha_reserva', $fecha)
            ->whereIn('estatus_operativo', ['ACTIVA', 'PENDIENTE'])
            ->where(function ($q) use ($ahora) {
                $q->where('estatus_operativo', 'ACTIVA')
                    ->orWhere('fecha_expiracion', '>', $ahora);
            })
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();

        if ($b2Reserva) {
            return 'Ya tienes una reservación en este horario. Cancélala primero para hacer una nueva.';
        }

        // 2b. Sus clases confirmadas para esta fecha/horario
        $b2Clase = \App\Models\InscripcionClase::where('id_usuario', $idSocio)
            ->where('tipo_usuario', 'socio_titular')
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->whereHas('sesion', function ($q) use ($fecha, $horaInicio, $horaFin) {
                $q->withoutGlobalScopes()
                    ->where('fecha_sesion', $fecha)
                    ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
                    ->whereHas('actividadPlantilla', function ($q2) use ($horaInicio, $horaFin) {
                        $q2->where('hora_inicio', '<', $horaFin)
                            ->where('hora_fin', '>', $horaInicio);
                    });
            })
            ->exists();

        if ($b2Clase) {
            return 'Tienes una clase programada en este horario. No puedes hacer una reservación que se empalme con tu agenda.';
        }

        // 2c. Sus encuentros de torneo para esta fecha/horario
        // Los encuentros usan competidor_1_id / competidor_2_id apuntando a participantes_torneo
        $misParticipanteIds = \App\Models\ParticipantesTorneo::where('participante_type', 'SOCIO')
            ->where('participante_id', $idSocio)
            ->pluck('id_participante_torneo')
            ->toArray();

        $b2Torneo = !empty($misParticipanteIds) && EncuentrosTorneo::whereDate('fecha_hora_inicio', $fecha)
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->whereNotNull('fecha_hora_inicio')
            ->whereNotNull('fecha_hora_fin')
            ->where('fecha_hora_inicio', '<', $fechaHoraFin)
            ->where('fecha_hora_fin', '>', $fechaHoraInicio)
            ->where(function ($q) use ($misParticipanteIds) {
                $q->whereIn('competidor_1_id', $misParticipanteIds)
                    ->orWhereIn('competidor_2_id', $misParticipanteIds);
            })
            ->exists();

        if ($b2Torneo) {
            return 'Tienes un encuentro de torneo en este horario. No puedes hacer una reservación que se empalme con tu agenda.';
        }

        return null;
    }

    private function validarPenalizacionReservas(\App\Models\User $user): ?array
    {
        if ($user->rol !== 'socio_titular') return null;

        $socio = SocioTitular::select(
            'id_socio', 'estatus_penalizacion', 'fecha_fin_penalizacion_reserva'
        )->find($user->user_id);

        if (!$socio) return null;

        $bloqueado = in_array($socio->estatus_penalizacion, ['PENALIZADO_RESERVA', 'PENALIZADO_AMBOS', 'SUSPENDIDO']);
        $vigente   = $socio->estatus_penalizacion === 'SUSPENDIDO'
            || ($socio->fecha_fin_penalizacion_reserva?->isFuture());

        if (!$bloqueado || !$vigente) return null;

        return [
            'success'          => false,
            'message'          => 'Tu cuenta tiene una penalización activa en Reservaciones. No puedes realizar nuevas reservas hasta que expire la sanción.',
            'fecha_liberacion' => $socio->fecha_fin_penalizacion_reserva?->toDateTimeString(),
        ];
    }
}