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

        // 1. PROCESAR INSCRIPCIONES A CLASES
        InscripcionClase::whereDate('fecha_transaccion', $hoy)
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->chunkById(100, function ($inscripciones) use ($hoy) {
                foreach ($inscripciones as $inscripcion) {
                    $existeEnMongo = RegistroAsistencia::where('socio_id', $inscripcion->id_usuario)
                        ->where('id_sesion', $inscripcion->id_sesion)
                        ->whereBetween('timestamp', [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()])
                        ->exists();

                    if (!$existeEnMongo) {
                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);
                        $this->aplicarPenalizacion($inscripcion->id_usuario, 'de la clase');
                    }
                }
            });

        // 2. PROCESAR RESERVACIONES DE ESPACIOS (ON-DEMAND)
        \App\Models\Reservacion::whereDate('fecha_reserva', $hoy)
            ->where('estatus_operativo', 'ACTIVA')
            ->chunkById(100, function ($reservaciones) use ($hoy) {
                foreach ($reservaciones as $reserva) {
                    // Para espacios, buscamos por socio_id y el id_espacio
                    $existeEnMongo = RegistroAsistencia::where('socio_id', $reserva->id_socio_titular)
                        ->where('id_espacio', $reserva->id_espacio)
                        ->whereBetween('timestamp', [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()])
                        ->exists();

                    if (!$existeEnMongo) {
                        $reserva->update(['estatus_operativo' => 'NO_SHOW']);
                        $this->aplicarPenalizacion($reserva->id_socio_titular, 'del espacio reservado');
                    }
                }
            });

        $this->info('Cruce políglota completado.');
    }

    /**
     * Lógica compartida para incrementar contador y penalizar.
     */
    private function aplicarPenalizacion($socioId, $contexto)
    {
        $socio = SocioTitular::find($socioId);
        if ($socio) {
            $socio->increment('contador_no_shows');
            $socio->refresh();

            if ($socio->contador_no_shows >= 3 && $socio->estatus_cuenta !== 'PENALIZADO') {
                $socio->estatus_cuenta = 'PENALIZADO';
                $socio->fecha_fin_penalizacion = \Carbon\Carbon::now('America/Mexico_City')->addDays(7)->startOfDay();
                $socio->save();
            }

            // Enviar notificación
            if ($socio->correo_electronico) {
                try {
                    Notification::route('mail', $socio->correo_electronico)
                        ->notify(new NoShowPenalization($contexto));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error enviando notificación no-show: " . $e->getMessage());
                }
            }
        }
    }
}
