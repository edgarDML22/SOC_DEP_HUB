<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReservationServices
{
    public function obtener_ids($data)
    {
        $id_socio = DB::table('socios_titulares')
            ->where('numero_accion', $data['numero_accion'])
            ->value('id_socio');

        return DB::table('reservaciones_on_demand')
            ->where('id_socio_titular', $id_socio)
            ->where('id_espacio', $data['id_espacio'])
            ->where('fecha_reserva', $data['fecha_reserva'])
            ->where('hora_inicio', $data['hora_inicio'])
            ->where('hora_fin', $data['hora_fin'])
            ->value('id_reserva');
    }
}
