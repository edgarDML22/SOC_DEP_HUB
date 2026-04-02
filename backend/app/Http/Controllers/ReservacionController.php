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






}
