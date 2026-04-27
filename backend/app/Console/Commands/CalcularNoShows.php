<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\InscripcionClase;
use App\Models\RegistroAsistencia;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NoShowPenalization;
use App\Models\SocioTitular;
use App\Models\ActividadPlantilla;

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

                    // 2. Cruzar con MongoDB (Atlas) usando los nombres correctos de tu captura
                    $existeEnMongo = RegistroAsistencia::where('socio_id', $inscripcion->id_usuario)
                        ->where('id_sesion', $inscripcion->id_sesion)
                        ->whereBetween('timestamp', [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()])
                        ->exists();

                    if (!$existeEnMongo) {

                        // 1. Cambiar estatus a FALTA
                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);

                        // 2. Incrementar contador y evaluar penalización
                        $socio = SocioTitular::find($inscripcion->id_usuario);

                        if ($socio) {
                            $socio->increment('contador_no_shows');
                            $socio->refresh();

                            // Al acumular 3 no_shows, penalizar por 7 días naturales
                            if ($socio->contador_no_shows >= 3 && $socio->estatus_cuenta !== 'PENALIZADO') {
                                $socio->estatus_cuenta = 'PENALIZADO';
                                $socio->fecha_fin_penalizacion = \Carbon\Carbon::now('America/Mexico_City')->addDays(7);
                                $socio->save();
                            }
                        }

                        // Traemos la sesión y cargamos su espacio físico
                        $sesion = ActividadPlantilla::with('espacioFisico')->find($inscripcion->id_sesion);

                        // Extraemos el nombre del espacio navegando por la relación
                        $nombreEspacio = ($sesion && $sesion->espacioFisico) ? $sesion->espacioFisico->nombre_espacio : 'las instalaciones';

                        // 4. Enviar notificación
                        if ($socio && $socio->correo_electronico) {
                            Notification::route('mail', $socio->correo_electronico)
                                ->notify(new NoShowPenalization($nombreEspacio));
                        }
                    }
                }
            });

        $this->info('Cruce políglota completado.');
    }
}
