<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige registros en drafts_programacion cuyo payload sea "[]" (array vacío).
 * El cast Eloquent 'array' con valor [] produce JSON array en lugar de objeto,
 * rompiendo el acceso a payload.actividades en el frontend.
 *
 * El UNIQUE constraint se omite aquí porque requiere permisos de owner;
 * aplicarlo manualmente: ALTER TABLE drafts_programacion ADD CONSTRAINT
 * drafts_programacion_id_gerente_unique UNIQUE (id_gerente);
 */
return new class extends Migration {
    public function up(): void
    {
        // Normalizar payload=[] a objeto canónico con actividades vacías
        DB::update("
            UPDATE drafts_programacion
            SET payload = '{\"nombre_plantilla\": null, \"fecha_inicio\": null, \"fecha_fin\": null, \"actividades\": []}'::jsonb
            WHERE payload = '[]'::jsonb
        ");
    }

    public function down(): void
    {
        // No reversible — no hay forma segura de volver a []
    }
};
