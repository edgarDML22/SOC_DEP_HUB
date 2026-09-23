<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Índice parcial: cubre exactamente la query de getActiveDraft().
        // Solo indexa filas PENDIENTE, que son una fracción mínima del total de la tabla.
        // Orden: id_socio_titular (igualdad) → fecha_expiracion (rango > NOW())
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_reservas_draft_activo
            ON reservaciones_on_demand (id_socio_titular, fecha_expiracion)
            WHERE estatus_operativo = \'PENDIENTE\'');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_reservas_draft_activo');
    }
};
