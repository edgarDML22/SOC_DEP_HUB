<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$enums = \Illuminate\Support\Facades\DB::select("SELECT enumlabel FROM pg_enum WHERE enumtypid = (SELECT oid FROM pg_type WHERE typname LIKE '%tipo_usuario%' LIMIT 1)");
echo "\nEnum values for tipo_usuario:\n";
foreach ($enums as $e) {
    echo "  - {$e->enumlabel}\n";
}
