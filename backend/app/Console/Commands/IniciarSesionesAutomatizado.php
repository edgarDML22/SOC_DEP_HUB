<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IniciarSesionesAutomatizado extends Command
{
    protected $signature   = 'sesiones:iniciar';
    protected $description = 'Cambia a EN_CURSO las sesiones cuya hora_inicio está dentro de los próximos 15 minutos.';

    public function handle(): int
    {
        $ahora = Carbon::now('America/Mexico_City');

        /*
         * Ventana de inicio: sesiones que deben pasar a EN_CURSO.
         *
         * Una sesión se activa cuando su hora_inicio está en el rango:
         *   [ahora - 0 min, ahora + 15 min]
         *
         * Es decir: la sesión ya debió haber comenzado o comienza en menos de 15 minutos.
         * El timestamp completo se construye concatenando fecha_sesion + hora_inicio
         * directamente en SQL para que PostgreSQL haga la comparación de forma eficiente
         * sin traer filas a PHP.
         *
         * Se usa withoutGlobalScopes() para saltar FuturasActivasScope, que excluye
         * CANCELADA pero también podría filtrar sesiones de hoy en ciertos bordes de fecha.
         */
        $limite = $ahora->copy()->addMinutes(15)->format('H:i:s');
        $hoy    = $ahora->toDateString();
        $horaActual = $ahora->format('H:i:s');

        $afectadas = DB::table('sesiones_activas')
            ->join('actividades_plantilla', 'sesiones_activas.id_actividad_plantilla', '=', 'actividades_plantilla.id_actividad_plantilla')
            ->whereNull('actividades_plantilla.deleted_at')          // respetar SoftDeletes de ActividadPlantilla
            ->where('sesiones_activas.fecha_sesion', $hoy)
            ->where('sesiones_activas.estatus_sesion', 'DISPONIBLE')
            ->where('actividades_plantilla.hora_inicio', '<=', $limite)  // empieza dentro de los próximos 15 min
            ->where('actividades_plantilla.hora_inicio', '>=', $horaActual) // no atrapar sesiones ya pasadas sin atender
            ->update(['sesiones_activas.estatus_sesion' => 'EN_CURSO']);

        if ($afectadas > 0) {
            Log::channel('stack')->info("sesiones:iniciar [{$ahora}] — {$afectadas} sesión(es) cambiadas a EN_CURSO.", [
                'hora_actual' => $horaActual,
                'ventana_hasta' => $limite,
                'fecha' => $hoy,
            ]);
            $this->info("[{$ahora->format('H:i:s')}] {$afectadas} sesión(es) iniciadas.");
        } else {
            $this->line("[{$ahora->format('H:i:s')}] Sin sesiones para iniciar.");
        }

        return self::SUCCESS;
    }
}
