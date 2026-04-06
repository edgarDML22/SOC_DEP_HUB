<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\ReservationServices;
use App\Http\Controllers\Controller;
use App\Models\Reservacion;

class CancelationController extends Controller
{
    protected $service;
    public function __construct(ReservationServices $service)
    {
        $this->service = $service;
    }
    /* Cancelar reservaciones */
    public function cancelar_reservacion(Request $request)
    {
        $id = $this->service->obtener_ids_reservaciones($request->all());
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro ninguna reservación'
            ]);
        }
        Reservacion::where('id_reserva', $id)
            ->update([
                'estatus_operativo' => 'CANCELADA',
            ]);
        return response()->json([
            'success' => true,
            'message' => 'Reservación cancelada correctamente'
        ]);
    }
    //
}

