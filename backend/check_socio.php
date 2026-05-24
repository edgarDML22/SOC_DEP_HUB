<?php
// Quick debug script — safe to delete
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SocioTitular;

// Check all users with socio role
$users = User::where('rol', 'socio_titular')->get();
foreach ($users as $u) {
    $socio = SocioTitular::find($u->user_id);
    echo "User #{$u->id} | user_id={$u->user_id} | rol={$u->rol}";
    if ($socio) {
        echo " | socio.estatus_cuenta={$socio->estatus_cuenta}";
    } else {
        echo " | SOCIO NOT FOUND";
    }
    echo "\n";
}

// Also check estatus_cuenta enum values
$enums = \Illuminate\Support\Facades\DB::select("SELECT enumlabel FROM pg_enum WHERE enumtypid = (SELECT oid FROM pg_type WHERE typname LIKE '%estatus_cuenta%' LIMIT 1)");
echo "\nEnum values for estatus_cuenta:\n";
foreach ($enums as $e) {
    echo "  - {$e->enumlabel}\n";
}
