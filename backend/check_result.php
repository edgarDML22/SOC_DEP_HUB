<?php
$notifs = \DB::table('notifications')
    ->where('notifiable_id', 4)
    ->where('notifiable_type', 'SOCIO')
    ->orderByDesc('created_at')
    ->limit(3)
    ->get();

echo "Ultimas 3 notifs del socio 4:\n";
foreach ($notifs as $n) {
    $data = json_decode($n->data, true);
    echo "  tipo=" . ($data['tipo'] ?? '?') . " leida=" . ($n->read_at ? 'si' : 'no') . " created=" . $n->created_at . "\n";
    if (($data['tipo'] ?? '') === 'SESION_CANCELADA') {
        echo "  disciplina=" . ($data['disciplina'] ?? '?') . " fecha=" . ($data['fecha_sesion'] ?? '?') . "\n";
    }
}

$failed = \DB::table('failed_jobs')->count();
echo "Failed jobs pendientes: $failed\n";
