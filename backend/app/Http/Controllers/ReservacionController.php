<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Models\SocioTitular;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use Illuminate\Support\Facades\Validator;

class ReservacionController extends Controller
{
    public function store(Request $request)
    {
        $id_socio = SocioTitular::where('numero_accion', $request->numero_accion)
            ->value('id_socio');
        if ($id_socio == null) {
            return response()->json([
                'success' => false,
                'message' => 'El numero de accion no existe'
            ]);
        }
        try {
            $request->validate([
                'fecha_reserva' => 'required|date|after_or_equal:today',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'id_espacio' => 'required|integer|exists:espacios_fisicos,id_espacio',

            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }

        /* Validar espacio inactivo */
        $activo = EspacioFisico::where('id_espacio', $request->id_espacio)
            ->where('estatus', 'ACTIVO')
            ->exists();

        if (!$activo) {
            return response()->json([
                'success' => false,
                'message' => 'El espacio no está disponible'
            ]);
        }
        /* Validar fecha */
        if ($request->fecha_reserva < date('Y-m-d')) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de reservación no puede ser menor a la fecha actual'
            ]);
        }
        /* Validar hora */
        if ($request->hora_inicio >= $request->hora_fin) {
            return response()->json([
                'success' => false,
                'message' => 'La hora de inicio debe ser menor que la hora de fin'
            ]);
        }
        /* SDH-92 */
        $empalme = Reservacion::where('fecha_reserva', $request->fecha_reserva)
            ->where(function ($query) use ($request) {
                $query->where('hora_inicio', '<', $request->hora_fin)
                    ->where('hora_fin', '>', $request->hora_inicio);
            })
            ->where('id_socio_titular', $id_socio)
            ->where('estatus_operativo', '!=', 'CANCELADA')
            ->exists();
        if ($empalme) {
            return response()->json([
                'success' => false,
                'message' => "Ya existe una actividad reservada en este horario"
            ]);
        }

        /* SDH-74   */

        return DB::transaction(function () use ($request) {
            $fecha_expiracion = Carbon::now()->addMinutes(15);
            $id_socio = SocioTitular::where('numero_accion', $request->numero_accion)
                ->value('id_socio');

            $conflicto = Reservacion::where('fecha_reserva', $request->fecha_reserva)
                ->where('estatus_operativo', '!=', 'CANCELADA')
                ->where(function ($query) use ($request) {
                    $query->where('hora_inicio', '<', $request->hora_fin)
                        ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->where('id_espacio', $request->id_espacio)
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Espacio agotado. Ya existe una reservacion en este horario',
                ]);
            } else {
                /*Agrega los datos a reservaciones on demand*/
                Reservacion::create([
                    'id_socio_titular' => $id_socio,
                    'id_espacio' => $request->id_espacio,
                    'fecha_reserva' => $request->fecha_reserva,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fin' => $request->hora_fin,
                    'estatus_operativo' => 'PENDIENTE',
                    'fecha_expiracion' => $fecha_expiracion,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservación agregada correctamente'
                ]);

            }

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
}