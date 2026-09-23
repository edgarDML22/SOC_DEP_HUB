<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NoShowOnDemand extends Command
{
    protected $signature = 'app:no-show-on-demand';

    protected $description = 'Marca como NO_SHOW las reservas de espacios ACTIVAS cuya hora_fin superó hace más de 15 minutos';

    public function handle(): int
    {
        $tz   = 'America/Mexico_City';
        $now  = Carbon::now($tz);
        $hoy  = $now->toDateString();

        // hora_fin + 15 min <= now  =>  hora_fin <= now - 15 min
        $limiteHora = $now->copy()->subMinutes(15)->toTimeString('minute'); // HH:MM

        $this->info("Ejecutando a {$now->toDateTimeString()} | límite hora_fin ≤ {$limiteHora}");

        // Consulta aprovecha el índice compuesto idx_reservaciones_no_show
        $reservas = DB::table('reservaciones_on_demand')
            ->where('fecha_reserva', $hoy)
            ->where('estatus_operativo', 'ACTIVA')
            ->where('hora_fin', '<=', $limiteHora)
            ->select('id_reserva', 'id_socio_titular')
            ->get();

        if ($reservas->isEmpty()) {
            $this->info('Sin reservas para marcar como NO_SHOW.');
            return self::SUCCESS;
        }

        $ids      = $reservas->pluck('id_reserva')->all();
        $socioIds = $reservas->pluck('id_socio_titular')->unique()->all();

        // Actualización masiva
        DB::table('reservaciones_on_demand')
            ->whereIn('id_reserva', $ids)
            ->update(['estatus_operativo' => 'NO_SHOW', 'updated_at' => $now]);

        // Incremento masivo del contador en socios_titulares
        // Cada socio puede tener N reservas vencidas; incrementamos por la cantidad real
        $conteosPorSocio = $reservas->groupBy('id_socio_titular')->map->count();

        foreach ($conteosPorSocio as $socioId => $cantidad) {
            DB::table('socios_titulares')
                ->where('id_socio', $socioId)
                ->increment('contador_no_shows', $cantidad);
        }

        $this->info("Procesados: {$reservas->count()} reserva(s) | Socios afectados: " . count($socioIds));

        return self::SUCCESS;
    }
}
