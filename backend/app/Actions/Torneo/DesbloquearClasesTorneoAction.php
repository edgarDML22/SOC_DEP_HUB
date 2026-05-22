<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Models\EncuentrosTorneo;
use App\Models\SesionActiva;

class DesbloquearClasesTorneoAction
{
    /**
     * Restaura las sesiones que fueron canceladas por el torneo
     * al estatus 'DISPONIBLE', siempre que su fecha sea futura.
     *
     * Se ejecuta cuando un torneo pasa a FINALIZADO o CANCELADO.
     *
     * @param Torneo $torneo
     */
    public function execute(Torneo $torneo): void
    {
        // 1. Obtener los id_espacio del torneo
        $espacioIds = EncuentrosTorneo::where('id_torneo', $torneo->id_torneo)
            ->whereNotNull('id_espacio')
            ->distinct()
            ->pluck('id_espacio')
            ->toArray();

        if (empty($espacioIds)) {
            return;
        }

        // 2. Restaurar sesiones CANCELADA_POR_TORNEO que sean futuras
        //    y pertenezcan a actividades en los espacios del torneo
        SesionActiva::withoutGlobalScopes()
            ->where('estatus_sesion', 'CANCELADA_POR_TORNEO')
            ->where('fecha_sesion', '>=', now()->toDateString())
            ->whereHas('actividadPlantilla', function ($q) use ($espacioIds) {
                $q->whereIn('id_espacio', $espacioIds);
            })
            ->update(['estatus_sesion' => 'DISPONIBLE']);
    }
}
