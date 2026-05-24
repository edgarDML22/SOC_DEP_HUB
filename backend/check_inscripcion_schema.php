<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = \Illuminate\Support\Facades\DB::select("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_name = 'inscripciones_clases'");
echo "Columns in inscripciones_clases:\n";
foreach ($columns as $c) {
    echo " - {$c->column_name} ({$c->data_type}) | default: {$c->column_default}\n";
}
