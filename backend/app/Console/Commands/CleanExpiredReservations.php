<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-expired-reservations';

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
        $afectados = \App\Models\Reservacion::where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '<', now())
            ->update(['estatus_operativo' => 'CANCELADA']);

        $this->info("Se limpiaron {$afectados} reservaciones expiradas.");
    }
}
