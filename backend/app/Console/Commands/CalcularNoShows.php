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
        InscripcionClase::publicadaYVigente()
            ->whereDate('fecha_transaccion', $hoy)
            ->where('estatus_inscripcion', 'CONFIRMADA')
            ->chunkById(100, function ($inscripciones) use ($hoy) {
                foreach ($inscripciones as $inscripcion) {
                    $asistio = RegistroAsistencia::where('id_sesion', $inscripcion->id_sesion)
                        ->where('id_usuario', $inscripcion->id_usuario)
                        ->where('asistencia', true)
                        ->exists();

                    if (!$asistio) {
                        $tipoRegistro = match (strtolower((string) $inscripcion->tipo_usuario)) {
                            'socio_titular', 'socio'        => 'SOCIO_TITULAR',
                            'miembro_familiar', 'familiar'  => 'MIEMBRO_FAMILIAR',
                            default                         => strtoupper((string) $inscripcion->tipo_usuario),
                        };

                        RegistroAsistencia::create([
                            'id_sesion'           => $inscripcion->id_sesion,
                            'id_usuario'          => $inscripcion->id_usuario,
                            'tipo_usuario'        => $tipoRegistro,
                            'asistencia'          => false,
                            'metodo_registro'     => 'SIN_REGISTRO',
                            'fecha_hora_registro' => now('America/Mexico_City'),
                        ]);

                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);
                        $this->aplicarPenalizacion($inscripcion->id_usuario, $inscripcion->tipo_usuario, 'de la clase');
                        \App\Jobs\ProcesarAlertaNoShowJob::dispatch($inscripcion);
                    }
                }
            });

        // 2. PROCESAR RESERVACIONES DE ESPACIOS (ON-DEMAND)
        \App\Models\Reservacion::whereDate('fecha_reserva', $hoy)
            ->where('estatus_operativo', 'ACTIVA')
            ->chunkById(100, function ($reservaciones) use ($hoy) {
                foreach ($reservaciones as $reserva) {
                    // Si la reserva expiró sin check-in ni asistencia
                    $reserva->update(['estatus_operativo' => 'NO_SHOW']);
                    $this->aplicarPenalizacion($reserva->id_socio_titular, 'socio_titular', 'del espacio reservado');
                }
            });

        $this->info('Cálculo de No-Shows y alertas completado.');
    }

    /**
     * Lógica compartida para incrementar contador y penalizar.
     */
    private function aplicarPenalizacion($usuarioId, $tipoUsuario = 'socio_titular', $contexto = 'de la clase')
    {
        $socioId = $usuarioId;

        if (in_array(strtolower((string) $tipoUsuario), ['miembro_familiar', 'familiar'], true)) {
            $familiar = \App\Models\MiembrosFamiliares::find($usuarioId);
            if ($familiar) {
                $familiar->increment('contador_no_shows');
                $socioId = $familiar->socio_id;
            }
        }

        $socio = SocioTitular::find($socioId);
        if ($socio) {
            $socio->increment('contador_no_shows');
            $socio->refresh();

            if ($socio->contador_no_shows >= 3 && !in_array($socio->estatus_cuenta, ['SUSPENDIDO', 'PENALIZADO'], true)) {
                $socio->estatus_cuenta = 'PENALIZADO';
                $socio->estatus_penalizacion = 'PENALIZADO';
                $socio->fecha_fin_penalizacion_reserva = \Carbon\Carbon::now('America/Mexico_City')->addDays(7)->startOfDay();
                $socio->save();
            }

            // Enviar notificación por correo si tiene
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
