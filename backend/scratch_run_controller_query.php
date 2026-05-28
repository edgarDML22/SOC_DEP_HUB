<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SesionActiva;
use Carbon\Carbon;

try {
    $tz = 'America/Mexico_City';
    $hoy = Carbon::now($tz)->toDateString();
    $fin = Carbon::now($tz)->addDays(30)->toDateString();

    echo "Hoy: $hoy | Fin: $fin\n\n";

    $query = SesionActiva::withoutGlobalScopes()
        ->whereBetween('fecha_sesion', [$hoy, $fin])
        ->where(function ($q) use ($hoy) {
            $q->where('fecha_sesion', $hoy)
              ->orWhere(function ($sub) use ($hoy) {
                  $sub->where('fecha_sesion', '>', $hoy)
                      ->whereIn('estatus_sesion', ['DISPONIBLE', 'LLENO']);
              });
        })
        ->whereHas('actividadPlantilla.plantilla', function ($q) {
            $q->where('estatus_plantilla', true)
                ->where('publicada', true)
                ->whereColumn('plantillas_programacion.fecha_inicio', '<=', 'sesiones_activas.fecha_sesion')
                ->whereColumn('plantillas_programacion.fecha_fin', '>=', 'sesiones_activas.fecha_sesion');
        });

    echo "SQL: " . $query->toSql() . "\n";
    echo "Bindings: " . json_encode($query->getBindings()) . "\n\n";

    $results = $query->get();
    echo "Total sesiones devueltas: " . $results->count() . "\n";
    foreach ($results as $s) {
        $p = $s->actividadPlantilla;
        echo "ID: {$s->id_sesion} | Fecha: {$s->fecha_sesion} | Disc: {$s->id_disciplina} | Estatus: {$s->estatus_sesion} | Plantilla ID: {$p->id_plantilla}\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
