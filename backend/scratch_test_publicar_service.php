<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tz = 'America/Mexico_City';
$hoy = '2026-05-27';
$lunes = '2026-05-25';
$domingo = '2026-05-31';

$plantilla = App\Models\PlantillaProgramacion::withoutGlobalScopes()
    ->with(['actividades' => fn($q) => $q->where('estatus', 'ACTIVO')])
    ->findOrFail(7);

$service = new App\Services\PublicarProgramacionService();
$rango = [
    'lunes' => Carbon\Carbon::createFromFormat('Y-m-d', $lunes)->startOfDay(),
    'domingo' => Carbon\Carbon::createFromFormat('Y-m-d', $domingo)->endOfDay(),
];

echo "Total actividades activas en plantilla: " . $plantilla->actividades->count() . "\n";
$miercolesActividades = $plantilla->actividades->where('dia_semana', 'MIERCOLES');
echo "Total miercoles activas en plantilla: " . $miercolesActividades->count() . "\n";
foreach($miercolesActividades as $act) {
    echo "ID: {$act->id_actividad_plantilla}, Disciplina ID: {$act->id_disciplina}, estatus: {$act->estatus}\n";
}

$rows = $service->proyectarSesiones($plantilla->actividades, $rango);
echo "Total filas proyectadas por el servicio: " . count($rows) . "\n";

$miercolesRows = array_filter($rows, fn($r) => $r['fecha_sesion'] === $hoy);
echo "Total filas proyectadas para hoy miercoles: " . count($miercolesRows) . "\n";
foreach($miercolesRows as $r) {
    echo "Plantilla Actividad ID: {$r['id_actividad_plantilla']}, Fecha: {$r['fecha_sesion']}, Disciplina ID: {$r['id_disciplina']}\n";
}
