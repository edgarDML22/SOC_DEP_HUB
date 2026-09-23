<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tz = 'America/Mexico_City';
$hoy = '2026-05-27'; // Miercoles

echo "--- ANALIZANDO ACTIVIDADES EN PLANTILLAS ACTIVAS PARA EL MIÉRCOLES ---\n";
// Buscar plantillas activas y publicadas hoy
$plantillas = App\Models\PlantillaProgramacion::where('estatus_plantilla', true)
    ->where('publicada', true)
    ->where('fecha_inicio', '<=', $hoy)
    ->where('fecha_fin', '>=', $hoy)
    ->get();

echo "Total plantillas activas y publicadas para hoy: " . $plantillas->count() . "\n";

foreach ($plantillas as $p) {
    echo "Plantilla ID: {$p->id_plantilla}, Nombre: {$p->nombre_plantilla}, Rango: {$p->fecha_inicio} a {$p->fecha_fin}\n";
    
    // Obtener actividades en esta plantilla que correspondan a MIERCOLES
    // Nota: El modelo podría llamarse ActividadPlantilla o similar. Busquemos en la base de datos o relaciones
    // Si no conocemos la relación, podemos ver la tabla directamente usando DB
    $actividades = DB::table('actividades_plantilla')
        ->where('id_plantilla', $p->id_plantilla)
        ->where('dia_semana', 'MIERCOLES')
        ->get();
        
    echo "  Actividades programadas en la plantilla para el MIÉRCOLES: " . $actividades->count() . "\n";
    foreach ($actividades as $act) {
        $disciplina = DB::table('disciplinas')->where('id_disciplina', $act->id_disciplina)->first();
        echo "    - Actividad ID: {$act->id_actividad_plantilla}, Disciplina: {$disciplina->nombre_disciplina}, Hora: {$act->hora_inicio} - {$act->hora_fin}\n";
        
        // Verificar si existe la sesión activa generada para esta actividad y para la fecha de hoy
        $sesion = DB::table('sesiones_activas')
            ->where('id_actividad_plantilla', $act->id_actividad_plantilla)
            ->where('fecha_sesion', $hoy)
            ->first();
            
        if ($sesion) {
            echo "      Generada en sesiones_activas: SÍ (ID: {$sesion->id_sesion}, Estatus: {$sesion->estatus_sesion})\n";
        } else {
            echo "      Generada en sesiones_activas: NO (¡Falta!)\n";
        }
    }
}
