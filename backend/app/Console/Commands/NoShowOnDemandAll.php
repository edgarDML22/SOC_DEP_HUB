<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NoShowOnDemandAll extends Command
{
    protected $signature = 'app:no-show-on-demand-all';

    protected $description = '[USO ÚNICO] Marca como NO_SHOW todas las reservas ACTIVAS vencidas hace +15 min, sin importar la fecha';

    public function handle(): int
    {
        $tz          = 'America/Mexico_City';
        $now         = Carbon::now($tz);
        $limiteHora  = $now->copy()->subMinutes(15)->toTimeString('minute');

        $this->info("Ejecutando a {$now->toDateTimeString()} | límite hora_fin ≤ {$limiteHora}");
        $this->warn('MODO FULL TABLE: revisando toda la tabla reservaciones_on_demand');

        $reservas = DB::table('reservaciones_on_demand')
            ->where('estatus_operativo', 'ACTIVA')
            ->whereRaw("(fecha_reserva || ' ' || hora_fin)::timestamp <= ?", [
                $now->copy()->subMinutes(15)->toDateTimeString()
            ])
            ->select('id_reserva', 'id_socio_titular')
            ->get();

        if ($reservas->isEmpty()) {
            $this->info('Sin reservas para marcar como NO_SHOW.');
            return self::SUCCESS;
        }

        $ids             = $reservas->pluck('id_reserva')->all();
        $conteosPorSocio = $reservas->groupBy('id_socio_titular')->map->count();

        DB::table('reservaciones_on_demand')
            ->whereIn('id_reserva', $ids)
            ->update(['estatus_operativo' => 'NO_SHOW', 'updated_at' => $now]);

        foreach ($conteosPorSocio as $socioId => $cantidad) {
            DB::table('socios_titulares')
                ->where('id_socio', $socioId)
                ->increment('contador_no_shows', $cantidad);
        }

        $this->info("Procesados: {$reservas->count()} reserva(s) | Socios afectados: " . count($conteosPorSocio));

        return self::SUCCESS;
    }
}
