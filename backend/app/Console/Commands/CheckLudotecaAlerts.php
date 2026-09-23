<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistrosLudoteca;
use App\Models\SocioTitular;
use Carbon\Carbon;
use App\Notifications\AlertaRecogidaNotification;

class CheckLudotecaAlerts extends Command
{
    protected $signature = 'ludoteca:check-alerts';

    public function handle()
    {
        $activos = RegistrosLudoteca::where('estatus_ludoteca', 'ACTIVA')->get();

        foreach ($activos as $registro) {

            $minutosRestantes = Carbon::now()
                ->diffInMinutes($registro->hora_limite, false);

            $socio = SocioTitular::find($registro->id_adulto_ingreso);

            if ($minutosRestantes <= 10 && !$registro->alerta_10_enviada) {
                // Mandar la de 10 minutos
                $socio->notify(
                    new AlertaRecogidaNotification(10)
                );

                // Marcamos ambas como enviadas por si el cron se saltó la de 30 min
                $registro->update([
                    'alerta_10_enviada' => true,
                    'alerta_30_enviada' => true
                ]);

            } elseif ($minutosRestantes <= 30 && !$registro->alerta_30_enviada) {
                // Mandar la de 30 minutos
                $socio->notify(
                    new AlertaRecogidaNotification(30, 'advertencia')
                );

                $registro->update([
                    'alerta_30_enviada' => true
                ]);
            }
        }

        return 0;
    }
}