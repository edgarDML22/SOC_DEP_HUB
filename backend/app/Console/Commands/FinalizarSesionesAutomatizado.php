<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinalizarSesionesAutomatizado extends Command
{
    protected $signature   = 'sesiones:finalizar';
    protected $description = 'Cambia a FINALIZADA las sesiones EN_CURSO cuya hora_fin superó hace más de 20 minutos.';

    public function handle(): int
    {
        $ahora = Carbon::now('America/Mexico_City');

        /*
         * Una sesión se finaliza cuando su hora_fin ocurrió hace más de 20 minutos.
         * Esto da un margen de gracia antes de cerrar la sesión automáticamente,
         * por si el instructor necesita unos minutos extra.
         *
         * hora_fin + 20 min < ahora  →  hora_fin < ahora - 20 min
         *
         * Igual que en IniciarSesiones, la comparación se hace en SQL con un JOIN
         * para un único UPDATE masivo sin cargar modelos en memoria.
         */
        $corte  = $ahora->copy()->subMinutes(20)->format('H:i:s');
        $hoy    = $ahora->toDateString();

        $afectadas = DB::table('sesiones_activas')
            ->join('actividades_plantilla', 'sesiones_activas.id_actividad_plantilla', '=', 'actividades_plantilla.id_actividad_plantilla')
            ->whereNull('actividades_plantilla.deleted_at')
            ->where('sesiones_activas.fecha_sesion', $hoy)
            ->where('sesiones_activas.estatus_sesion', 'EN_CURSO')
            ->where('actividades_plantilla.hora_fin', '<', $corte)   // hora_fin ya superó el margen de 20 min
            ->update(['sesiones_activas.estatus_sesion' => 'FINALIZADA']);

        if ($afectadas > 0) {
            Log::channel('stack')->info("sesiones:finalizar [{$ahora}] — {$afectadas} sesión(es) cambiadas a FINALIZADA.", [
                'corte_hora_fin' => $corte,
                'fecha' => $hoy,
            ]);
            $this->info("[{$ahora->format('H:i:s')}] {$afectadas} sesión(es) finalizadas.");
        } else {
            $this->line("[{$ahora->format('H:i:s')}] Sin sesiones para finalizar.");
        }

        return self::SUCCESS;
    }
}
