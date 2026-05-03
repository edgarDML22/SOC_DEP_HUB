<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SocioTitular;
use App\Notifications\SancionLevantadaNotification;
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

    public function handle()
    {
        $ahora = Carbon::now('America/Mexico_City');

        // Socios con penalización activa (excluyendo SUSPENDIDO — esos no expiran automáticamente)
        $socios = SocioTitular::whereIn('estatus_penalizacion', [
                'PENALIZADO_LUDOTECA',
                'PENALIZADO_RESERVA',
                'PENALIZADO_AMBOS',
            ])
            ->where(function ($q) use ($ahora) {
                $q->where(function ($sub) use ($ahora) {
                    $sub->whereNotNull('fecha_fin_penalizacion_ludoteca')
                        ->where('fecha_fin_penalizacion_ludoteca', '<=', $ahora);
                })->orWhere(function ($sub) use ($ahora) {
                    $sub->whereNotNull('fecha_fin_penalizacion_reserva')
                        ->where('fecha_fin_penalizacion_reserva', '<=', $ahora);
                });
            })
            ->get();

        $count = 0;

        foreach ($socios as $socio) {
            $ludotecaExpirada = $socio->fecha_fin_penalizacion_ludoteca && $socio->fecha_fin_penalizacion_ludoteca->lte($ahora);
            $reservaExpirada  = $socio->fecha_fin_penalizacion_reserva  && $socio->fecha_fin_penalizacion_reserva->lte($ahora);
            $ludotecaActiva   = $socio->fecha_fin_penalizacion_ludoteca && $socio->fecha_fin_penalizacion_ludoteca->gt($ahora);
            $reservaActiva    = $socio->fecha_fin_penalizacion_reserva  && $socio->fecha_fin_penalizacion_reserva->gt($ahora);

            $liberacionCompleta = false;

            if ($ludotecaExpirada && $reservaExpirada) {
                $socio->estatus_penalizacion            = 'SIN_PENALIZACION';
                $socio->fecha_fin_penalizacion_ludoteca = null;
                $socio->fecha_fin_penalizacion_reserva  = null;
                $socio->contador_no_shows               = 0;
                $liberacionCompleta = true;
                $this->line("  ✓ Ambas penalizaciones levantadas: {$socio->nombre_completo} (ID {$socio->id_socio})");
            } elseif ($ludotecaExpirada && $reservaActiva) {
                $socio->estatus_penalizacion            = 'PENALIZADO_RESERVA';
                $socio->fecha_fin_penalizacion_ludoteca = null;
                $this->line("  ✓ Pen. ludoteca levantada (reserva sigue activa): {$socio->nombre_completo} (ID {$socio->id_socio})");
            } elseif ($reservaExpirada && $ludotecaActiva) {
                $socio->estatus_penalizacion           = 'PENALIZADO_LUDOTECA';
                $socio->fecha_fin_penalizacion_reserva = null;
                $this->line("  ✓ Pen. reserva levantada (ludoteca sigue activa): {$socio->nombre_completo} (ID {$socio->id_socio})");
            }

            $socio->save();

            // Notificar solo cuando la penalización queda en SIN_PENALIZACION
            if ($liberacionCompleta) {
                $socio->notify(new SancionLevantadaNotification(
                    nombre_socio: $socio->nombre_completo,
                    motivo:       'automatico',
                ));
            }

            $count++;
        }

        $this->info("Penalizaciones procesadas: {$count} socio(s) actualizados.");
    }
}
