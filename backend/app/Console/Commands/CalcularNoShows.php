<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use App\Models\InscripcionClase;
use App\Models\RegistroAsistencia;

class CalcularNoShows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calcular-no-shows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoy = \Carbon\Carbon::today('America/Mexico_City');

        // 1. Buscar en Postgres (Neon)
        InscripcionClase::whereDate('fecha_transaccion', $hoy)
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->chunkById(100, function ($inscripciones) use ($hoy) {
                foreach ($inscripciones as $inscripcion) {

                    // 2. Cruzar con MongoDB (Atlas)
                    $existeEnMongo = RegistroAsistencia::where('socio_id', $inscripcion->socio_id)
                        ->where(function ($q) use ($inscripcion) {
                        $q->where('clase_id', $inscripcion->clase_id)
                            ->orWhere('espacio_id', $inscripcion->espacio_id);
                    })
                        ->whereBetween('created_at', [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()])
                        ->exists();

                    // 3. Aplicar Reglas de Negocio
                    if (!$existeEnMongo) {
                        // Marcar No-Show
                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);

                        // Incrementar contador en la tabla relacionada
                        DB::table('socios_titulares')
                            ->where('id', $inscripcion->socio_id)
                            ->increment('contador_no_shows');
                    }
                }
            });

        $this->info('Cruce políglota completado.');
    }
}
