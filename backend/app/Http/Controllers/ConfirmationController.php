<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\ReservationServices;

class ConfirmationController extends Controller
{
    protected $service;
    public function __construct(ReservationServices $service)
    {
        $this->service = $service;
    }
    /* Confirmar reservaciones */
    public function confirmar_reservacion(Request $request)
    {

        $id = $this->service->obtener_ids($request->all());

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro ninguna reservación'
            ]);
        }
        $fecha_expiracion = DB::table('reservaciones_on_demand')
            ->where('id_reserva', $id)
            ->value('fecha_expiracion');

        if (!$fecha_expiracion) {
            return response()->json([
                'success' => false,
                'message' => 'La reservación no tiene fecha de expiración'
            ]);
        }

        $fecha_expiracion = Carbon::parse($fecha_expiracion);
        if ($fecha_expiracion < Carbon::now()) {
            return response()->json([
                'success' => false,
                'message' => 'La reservación ha expirado'
            ]);
        }

        /* Modificar datos */
        DB::table('reservaciones_on_demand')
            ->where('id_reserva', $id)
            ->update([
                'estatus_operativo' => 'ACTIVA',
                'fecha_expiracion' => null,
            ]);
        return response()->json([
            'success' => true,
            'message' => 'Reservación confirmada correctamente'
        ]);
    }
    //
}