<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $plantilla = DB::table('plantillas_programacion')->where('id_plantilla', 7)->first();
    if ($plantilla) {
        echo "=== PLANTILLA ID 7 ===\n";
        echo "Nombre: {$plantilla->nombre_plantilla}\n";
        echo "Estatus: " . ($plantilla->estatus_plantilla ? 'ACTIVO' : 'INACTIVO') . "\n";
        echo "Publicada: " . ($plantilla->publicada ? 'SÍ' : 'NO') . "\n";
        echo "Fecha Inicio: {$plantilla->fecha_inicio}\n";
        echo "Fecha Fin: {$plantilla->fecha_fin}\n";
    } else {
        echo "No se encontró la plantilla ID 7\n";
    }

    echo "\n=== TODAS LAS PLANTILLAS PUBLICADAS ===\n";
    $plantillas = DB::table('plantillas_programacion')
        ->where('estatus_plantilla', true)
        ->where('publicada', true)
        ->get();
    foreach ($plantillas as $pl) {
        echo "ID: {$pl->id_plantilla} | Nombre: {$pl->nombre_plantilla} | Inicio: {$pl->fecha_inicio} | Fin: {$pl->fecha_fin}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
