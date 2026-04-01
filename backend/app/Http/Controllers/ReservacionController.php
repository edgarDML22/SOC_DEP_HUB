<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservacionController extends Controller
{
    /**
     * Endpoint para agregar acompañantes a una reservación existente.
     * 
     * POST /api/v1/reservaciones/{id}/acompanantes
     */
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
