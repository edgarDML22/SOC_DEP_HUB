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

            if ($minutosRestantes <= 30 && !$registro->alerta_30_enviada) {

                $socio->notify(
                    new AlertaRecogidaNotification(30)
                );

                $registro->update([
                    'alerta_30_enviada' => true
                ]);
            }

            if ($minutosRestantes <= 10 && !$registro->alerta_10_enviada) {

                $socio->notify(
                    new AlertaRecogidaNotification(10)
                );

                $registro->update([
                    'alerta_10_enviada' => true
                ]);
            }
        }

        return 0;
    }
}