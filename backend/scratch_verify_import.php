<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== VERIFICACIÓN DE BASE DE DATOS DE SOCIOS ===\n\n";

    // 1. Conteo de tablas
    $totalUsers = DB::table('users')->count();
    $totalTitulares = DB::table('socios_titulares')->count();
    $totalMiembros = DB::table('miembros_familiares')->count();

    echo "📊 Resumen de registros:\n";
    echo "  - Total Usuarios en la tabla 'users': $totalUsers\n";
    echo "  - Total Socios Titulares: $totalTitulares\n";
    echo "  - Total Miembros Familiares: $totalMiembros\n";
    echo "  - Total General (Socio + Familiares): " . ($totalTitulares + $totalMiembros) . "\n\n";

    // 2. Miembros huérfanos (sin titular asociado en la BD)
    $miembrosHuerfanos = DB::table('miembros_familiares')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('socios_titulares')
                  ->whereRaw('socios_titulares.id_socio = miembros_familiares.socio_id');
        })
        ->count();

    echo "🔍 Integridad Referencial:\n";
    if ($miembrosHuerfanos === 0) {
        echo "  ✅ ¡Excelente! Todos los miembros familiares están vinculados correctamente a un socio titular (0 huérfanos).\n";
    } else {
        echo "  ❌ ADVERTENCIA: Se encontraron $miembrosHuerfanos miembros familiares huérfanos.\n";
    }

    // 3. Obtener un ejemplo de socio con familia para mostrar estructura
    $ejemploSocio = DB::table('socios_titulares')
        ->join('users', 'socios_titulares.id_socio', '=', 'users.user_id')
        ->select('socios_titulares.id_socio', 'socios_titulares.numero_accion', 'socios_titulares.nombre_completo', 'users.email')
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('miembros_familiares')
                  ->whereRaw('miembros_familiares.socio_id = socios_titulares.id_socio');
        })
        ->first();

    if ($ejemploSocio) {
        $miembrosEjemplo = DB::table('miembros_familiares')
            ->where('miembros_familiares.socio_id', $ejemploSocio->id_socio)
            ->select('miembros_familiares.nombre_completo', 'miembros_familiares.parentesco')
            ->get();

        echo "\n👨‍👩‍👧‍👦 Ejemplo de vinculación familiar en la Base de Datos:\n";
        echo "  Titular: {$ejemploSocio->nombre_completo} (Acción: {$ejemploSocio->numero_accion}) | Email: {$ejemploSocio->email}\n";
        if ($miembrosEjemplo->isNotEmpty()) {
            foreach ($miembrosEjemplo as $m) {
                echo "    └─ Familiar: {$m->nombre_completo} ({$m->parentesco})\n";
            }
        } else {
            echo "    └─ (Sin familiares vinculados)\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error durante la verificación: " . $e->getMessage() . "\n";
}

