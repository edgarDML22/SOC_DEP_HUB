<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log; // <-- Importamos el Log

class CleanExpiredReservations extends Command
{
    protected $signature = 'app:clean-expired-reservations';

    protected $description = 'Limpia las reservaciones On Demand que superaron sus 15 minutos en estado PENDIENTE';

    public function handle()
    {
        $ahora = now();
        $this->info("Ejecutando limpieza a las: " . $ahora);

        $afectados = \App\Models\Reservacion::where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '<', $ahora)
            ->update(['estatus_operativo' => 'CANCELADA']);

        if ($afectados > 0) {
            // Guardamos rastro en storage/logs/laravel.log
            Log::info("Job CleanExpiredReservations: Se cancelaron {$afectados} reservaciones expiradas a las {$ahora}.");
            $this->info("Se limpiaron {$afectados} reservaciones expiradas.");
        } else {
            $this->info("No se encontraron reservaciones expiradas.");
        }
    }
}