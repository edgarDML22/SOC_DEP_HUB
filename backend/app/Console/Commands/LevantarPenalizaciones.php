<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SocioTitular;
use Carbon\Carbon;

class LevantarPenalizaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:levantar-penalizaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa los socios penalizados y levanta la sanción si han pasado 7 días naturales. Resetea el contador de No Shows a 0.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ahora = Carbon::now('America/Mexico_City');

        // Buscar socios en estado PENALIZADO cuya fecha de fin de penalización ya venció
        $sociosPenalizados = SocioTitular::where('estatus_cuenta', 'PENALIZADO')
            ->whereNotNull('fecha_fin_penalizacion')
            ->where('fecha_fin_penalizacion', '<=', $ahora)
            ->get();

        $count = 0;

        foreach ($sociosPenalizados as $socio) {
            $socio->estatus_cuenta = 'AL_CORRIENTE';
            $socio->contador_no_shows = 0;
            $socio->fecha_fin_penalizacion = null;
            $socio->save();

            $count++;
            $this->line("  ✓ Penalización levantada: {$socio->nombre_completo} (ID {$socio->id_socio})");
        }

        $this->info("Penalizaciones levantadas: {$count} socio(s) restituidos.");
    }
}
