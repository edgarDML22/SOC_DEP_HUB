<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanExpiredReservations extends Command
{
    protected $signature = 'app:clean-expired-reservations';

    protected $description = 'Limpia y elimina permanentemente las reservaciones On Demand que superaron sus 15 minutos en estado PENDIENTE';

    public function handle()
    {
        // 1. FORZAR LA HORA A LA MISMA ZONA HORARIA CON LA QUE SE CREÓ LA RESERVA
        $ahoraMexico = Carbon::now('America/Mexico_City');
        $this->info("Ejecutando limpieza a las: " . $ahoraMexico);

        // Al usar delete() directamente, eliminamos físicamente de la base de datos
        $afectados = \App\Models\Reservacion::where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '<', $ahoraMexico) // Usamos la variable forzada
            ->delete();

        if ($afectados > 0) {
            Log::info("Job CleanExpiredReservations: Se eliminaron permanentemente {$afectados} borradores basura expirados a las {$ahoraMexico}.");
            $this->info("Se eliminaron permanentemente {$afectados} borradores expirados.");
        } else {
            $this->info("No se encontraron reservaciones expiradas.");
        }
    }
}