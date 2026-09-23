<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Torneo;
$torneos = Torneo::all();
foreach ($torneos as $t) {
    echo "ID: " . $t->id_torneo . " | Nombre: " . $t->nombre_torneo . " | Pool: " . json_encode($t->pool_arbitros) . "\n";
}
