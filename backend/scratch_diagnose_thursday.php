<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $sessionIds = [322, 323];
    foreach ($sessionIds as $id) {
        echo "=== DIAGNÓSTICO SESIÓN ID: $id ===\n";
        $sesion = DB::table('sesiones_activas')->where('id_sesion', $id)->first();
        if (!$sesion) {
            echo "La sesión no existe en la base de datos!\n";
            continue;
        }

        echo "Fecha: {$sesion->fecha_sesion} | Estatus: {$sesion->estatus_sesion} | Act Plantilla ID: {$sesion->id_actividad_plantilla}\n";

        $actividad = DB::table('actividades_plantilla')->where('id_actividad_plantilla', $sesion->id_actividad_plantilla)->first();
        if (!$actividad) {
            echo "ADVERTENCIA: No se encontró la actividad de plantilla asociada ID {$sesion->id_actividad_plantilla}!\n";
            continue;
        }

        echo "Actividad Plantilla -> ID Plantilla: " . ($actividad->id_plantilla ?? "NULO") . " | Estatus: {$actividad->estatus} | Deleted At: " . ($actividad->deleted_at ?? "NULO") . "\n";

        if ($actividad->id_plantilla) {
            $plantilla = DB::table('plantillas_programacion')->where('id_plantilla', $actividad->id_plantilla)->first();
            if (!$plantilla) {
                echo "ADVERTENCIA: No se encontró la plantilla de programación asociada ID {$actividad->id_plantilla}!\n";
                continue;
            }

            echo "Plantilla -> Nombre: {$plantilla->nombre_plantilla} | Activo: " . ($plantilla->estatus_plantilla ? 'SÍ' : 'NO') . " | Publicada: " . ($plantilla->publicada ? 'SÍ' : 'NO') . " | Inicio: {$plantilla->fecha_inicio} | Fin: {$plantilla->fecha_fin}\n";
            
            // Check dates
            $inicioValido = $plantilla->fecha_inicio <= $sesion->fecha_sesion;
            $finValido = $plantilla->fecha_fin >= $sesion->fecha_sesion;
            echo "¿Fecha inicio <= fecha sesión? " . ($inicioValido ? 'SÍ' : 'NO') . "\n";
            echo "¿Fecha fin >= fecha sesión? " . ($finValido ? 'SÍ' : 'NO') . "\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
