<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$hoy = '2026-05-24';
$fin = '2026-06-23';

$query = App\Models\SesionActiva::withoutGlobalScopes()
    ->whereBetween('fecha_sesion', [$hoy, $fin])
    ->whereIn('estatus_sesion', ['DISPONIBLE', 'LLENO'])
    ->whereHas('actividadPlantilla.plantilla', function ($q) {
        $q->where('estatus_plantilla', true)
          ->where('publicada', true)
          ->whereColumn('plantillas_programacion.fecha_inicio', '<=', 'sesiones_activas.fecha_sesion')
          ->whereColumn('plantillas_programacion.fecha_fin', '>=', 'sesiones_activas.fecha_sesion');
    });

echo $query->toSql() . "\n";
print_r($query->getBindings());
echo "\nTotal matching sessions: " . $query->count() . "\n";
foreach($query->get() as $s) {
    $p = $s->actividadPlantilla->plantilla;
    echo "ID: {$s->id_sesion}, Fecha: {$s->fecha_sesion}, Plantilla ID: {$p->id_plantilla}, Inicio: {$p->fecha_inicio}, Fin: {$p->fecha_fin}\n";
}
