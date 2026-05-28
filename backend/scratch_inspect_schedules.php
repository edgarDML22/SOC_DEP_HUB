<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== ACTIVIDADES DE PLANTILLA (JUEVES y VIERNES) ===\n";
    $plantillas = DB::table('actividades_plantilla')
        ->select('actividades_plantilla.*', 'disciplinas.nombre_disciplina', 'instructores.nombre_completo as nombre_instructor', 'espacios_fisicos.nombre_espacio')
        ->leftJoin('disciplinas', 'actividades_plantilla.id_disciplina', '=', 'disciplinas.id_disciplina')
        ->leftJoin('instructores', 'actividades_plantilla.id_instructor', '=', 'instructores.id_instructor')
        ->leftJoin('espacios_fisicos', 'actividades_plantilla.id_espacio', '=', 'espacios_fisicos.id_espacio')
        ->whereIn('dia_semana', ['JUEVES', 'VIERNES'])
        ->orderBy('dia_semana')
        ->orderBy('hora_inicio')
        ->get();

    foreach ($plantillas as $p) {
        echo "ID: {$p->id_actividad_plantilla} | Dia: {$p->dia_semana} | Act: {$p->nombre_disciplina} | Hora: {$p->hora_inicio} - {$p->hora_fin} | Instructor: {$p->nombre_instructor} (ID: {$p->id_instructor}) | Espacio: {$p->nombre_espacio} | Estatus: {$p->estatus}\n";
    }

    echo "\n=== SESIONES ACTIVAS (FECHAS JUEVES y VIERNES PRÓXIMOS: 2026-05-28 y 2026-05-29) ===\n";
    $sesiones = DB::table('sesiones_activas')
        ->select('sesiones_activas.*', 'disciplinas.nombre_disciplina', 'instructores.nombre_completo as nombre_instructor', 'espacios_fisicos.nombre_espacio')
        ->leftJoin('disciplinas', 'sesiones_activas.id_disciplina', '=', 'disciplinas.id_disciplina')
        ->leftJoin('instructores', 'sesiones_activas.id_instructor', '=', 'instructores.id_instructor')
        ->leftJoin('espacios_fisicos', 'sesiones_activas.id_espacio', '=', 'espacios_fisicos.id_espacio')
        ->whereIn('fecha_sesion', ['2026-05-28', '2026-05-29'])
        ->orderBy('fecha_sesion')
        ->orderBy('hora_inicio')
        ->get();

    foreach ($sesiones as $s) {
        echo "ID: {$s->id_sesion} | Fecha: {$s->fecha_sesion} | Act: {$s->nombre_disciplina} | Hora: {$s->hora_inicio} - {$s->hora_fin} | Instructor: {$s->nombre_instructor} (ID: {$s->id_instructor}) | Espacio: {$s->nombre_espacio} | Estatus: {$s->estatus_sesion} | Inscritos: {$s->cantidad_inscritos}\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
