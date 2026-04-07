<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use Illuminate\Support\Facades\Validator;
use App\Models\SesionActiva;

class ReservacionController extends Controller
{
    // 1. CREAR RESERVA (Paso 3 del Front)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_socio' => 'required|integer|exists:socios_titulares,id_socio',
                'fecha_reserva' => 'required|date|after_or_equal:today',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'id_espacio' => 'required|integer|exists:espacios_fisicos,id_espacio',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $activo = EspacioFisico::where('id_espacio', $request->id_espacio)->where('estatus', 'ACTIVO')->exists();
        if (!$activo) {
            return response()->json(['success' => false, 'message' => 'El espacio no está disponible'], 400);
        }

        $inicio = Carbon::parse($request->hora_inicio);
        $fin = Carbon::parse($request->hora_fin);

        if ($inicio->diffInMinutes($fin) > 120) {
            return response()->json(['success' => false, 'message' => 'La reservación no puede exceder las 2 horas.'], 400);
        }

        return DB::transaction(function () use ($request) {

            // 1. VALIDAR EMPALMES CON OTRAS RESERVACIONES
            // ... dentro del transaction ...
            $conflictoReserva = Reservacion::where('id_espacio', $request->id_espacio)
                ->where('fecha_reserva', $request->fecha_reserva)
                ->where(function ($q) {
                    $q->where('estatus_operativo', 'ACTIVA') // Reservas firmes
                        ->orWhere(function ($sub) {
                            $sub->where('estatus_operativo', 'PENDIENTE')
                                ->where('fecha_expiracion', '>', now()); // PENDIENTES vivas
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

            // ** PENDIENTE ** VALIDAR EMPALMES CON encuentros_torneos

            if ($conflictoReserva || $conflictoSesion) {
                return response()->json(['success' => false, 'message' => 'Espacio agotado. Ya existe una actividad en este horario'], 409);
            }

            // SI TODO ESTÁ LIBRE, CREAMOS LA RESERVA
            $nuevaReserva = Reservacion::create([
                'id_socio_titular' => $request->id_socio,
                'id_espacio' => $request->id_espacio,
                'fecha_reserva' => $request->fecha_reserva,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'estatus_operativo' => 'PENDIENTE',
                'fecha_expiracion' => Carbon::now()->addMinutes(15),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservación agregada correctamente',
                'id_reserva' => $nuevaReserva->id_reserva
            ]);
        });
    }

    public function addAcompanante(Request $request, $id)
    {
        // Validación de datos entrantes (se asume que se recibe acompanante_id)
        $validator = Validator::make($request->all(), [
            'acompanante_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan parámetros requeridos o son inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $acompananteId = $request->acompanante_id;

        // 1. El usuario (acompañante) debe existir
        // Se valida en la tabla 'users' y/o 'socios_titulares' y 'miembros_familiares'
        $existeEnUsers = DB::table('users')->where('id', $acompananteId)->exists();
        $existeEnSocios = DB::table('socios_titulares')->where('id_socio', $acompananteId)->exists();
        $existeEnFamiliares = DB::table('miembros_familiares')->where('id_miembro', $acompananteId)->exists();

        if (!$existeEnUsers && !$existeEnSocios && !$existeEnFamiliares) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante especificado no existe.'
            ], 404);
        }

        // Obtener datos de la reservación padre
        $reservacion = DB::table('reservaciones')->where('id', $id)->first();
        if (!$reservacion) {
            return response()->json([
                'success' => false,
                'message' => 'La reservación a la que intentas agregar el acompañante no existe.'
            ], 404);
        }

        // 2. Validación de conflicto de horario
        // Validar que el acompañante no tenga otra reservación activa en el mismo horario
        $tieneConflicto = DB::table('reservaciones_usuarios')
            ->join('reservaciones', 'reservaciones.id', '=', 'reservaciones_usuarios.reservacion_id')
            ->where('reservaciones_usuarios.usuario_id', $acompananteId)
            ->where('reservaciones.fecha', $reservacion->fecha)
            ->where('reservaciones.estatus', '!=', 'cancelada')
            ->where(function ($query) use ($reservacion) {
                // Hay conflicto si el inicio de otra es antes del fin, y su fin es después del inicio
                $query->where('reservaciones.hora_inicio', '<', $reservacion->hora_fin)
                    ->where('reservaciones.hora_fin', '>', $reservacion->hora_inicio);
            })
            ->where('reservaciones.id', '!=', $id) // Excluir esta misma reservación
            ->exists();

        // Si es titular y hace sus propias reservas, verificamos con tabla "reservaciones"
        $tieneConflictoTitular = DB::table('reservaciones')
            ->where('usuario_id', $acompananteId) // dueño de reserva
            ->where('fecha', $reservacion->fecha)
            ->where('estatus', '!=', 'cancelada')
            ->where(function ($query) use ($reservacion) {
                $query->where('hora_inicio', '<', $reservacion->hora_fin)
                    ->where('hora_fin', '>', $reservacion->hora_inicio);
            })
            ->where('id', '!=', $id)
            ->exists();

        if ($tieneConflicto || $tieneConflictoTitular) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante tiene conflicto de horario con otra reservación.'
            ], 409);
        }

        // 3. Validación: No superar la capacidad máxima del espacio
        $espacio = DB::table('espacios')->where('id', $reservacion->espacio_id)->first();
        if ($espacio && isset($espacio->capacidad)) {
            // Contar asistentes (1 titular + cantidad en tabla pivote para esta reservacion)
            $asistentesAdicionales = DB::table('reservaciones_usuarios')
                ->where('reservacion_id', $id)
                ->count();

            $totalAsistentes = 1 + $asistentesAdicionales;

            if ($totalAsistentes >= $espacio->capacidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se ha alcanzado la capacidad máxima del espacio'
                ], 400);
            }
        }

        // Validar que no se haya agregado ya a este usuario a esta misma reserva
        $yaAgregado = DB::table('reservaciones_usuarios')
            ->where('reservacion_id', $id)
            ->where('usuario_id', $acompananteId)
            ->exists();

        if ($yaAgregado) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante ya ha sido agregado a esta reservación o es el titular.'
            ], 400);
        }

        // Insertar en tabla pivote de acompañantes
        DB::table('reservaciones_usuarios')->insert([
            'reservacion_id' => $id,
            'usuario_id' => $acompananteId,
            'agregado_por' => $request->user()->id ?? null,
            // 'created_at' => now(), // Descomentar si la tabla usa timestamps en DB
            // 'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Acompañante agregado exitosamente.'
        ], 201);
    }

    // 2. CONFIRMAR RESERVA (Paso 5 del Front)
    public function confirm(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        $reserva = Reservacion::where('id_reserva', $id)->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'No se encontró la reservación'], 404);
        }

        if (!$reserva->fecha_expiracion) {
            return response()->json(['success' => false, 'message' => 'La reservación no tiene fecha de expiración'], 400);
        }

        if (Carbon::parse($reserva->fecha_expiracion)->isPast()) {
            return response()->json(['success' => false, 'message' => 'La reservación ha expirado'], 400);
        }

        $reserva->update([
            'estatus_operativo' => 'ACTIVA',
            'fecha_expiracion' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Reservación confirmada correctamente']);
    }

    // 3. CANCELAR RESERVA (Si el usuario se sale a la mitad)
    public function cancel(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        Reservacion::where('id_reserva', $id)->update(['estatus_operativo' => 'CANCELADA']);

        return response()->json(['success' => true, 'message' => 'Reservación cancelada correctamente']);
    }

    public function getActiveDraft(Request $request)
    {
        $reserva = Reservacion::where('id_socio_titular', $request->id_socio)
            ->where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '>', now())
            ->with('espacioFisico')
            ->first();

        return response()->json([
            'success' => !!$reserva,
            'reserva' => $reserva
        ]);
    }
}
