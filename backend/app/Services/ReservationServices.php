<?php

namespace App\Services;

use App\Models\Reservacion;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\DB;

class ReservationServices
{
    public function obtener_ids_reservaciones($data)
    {
        $id_socio = SocioTitular::where('numero_accion', $data['numero_accion'])
            ->value('id_socio');

        return Reservacion::where('id_socio_titular', $id_socio)
            ->where('id_espacio', $data['id_espacio'])
            ->where('fecha_reserva', $data['fecha_reserva'])
            ->where('hora_inicio', $data['hora_inicio'])
            ->where('hora_fin', $data['hora_fin'])
            ->value('id_reserva');
    }
}
