<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PasesDiarios;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpireGuestPasses extends Command
{
    // El nombre con el que ejecutarás el comando
    protected $signature = 'passes:expire';

    // Descripción para cuando listes los comandos
    protected $description = 'Expira los pases de invitado activos al final del día';

    public function handle()
    {
        $ahora = Carbon::now('America/Mexico_City')->format('Y-m-d');

        $affected = PasesDiarios::where('estatus_acceso', 'ACTIVO')
            ->where('fecha_activacion', '<=', $ahora)
            ->update(['estatus_acceso' => 'EXPIRADO']);

        $this->info("Proceso terminado. {$affected} pases han sido marcados como 'EXPIRADO'.");
    }

}