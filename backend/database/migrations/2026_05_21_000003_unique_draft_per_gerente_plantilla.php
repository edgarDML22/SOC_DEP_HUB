<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el modelo de "un draft por gerente" a "un draft por (gerente + plantilla)".
 * Pasos:
 *  1. Eliminar el índice simple idx_drafts_gerente
 *  2. Crear unique index compuesto (id_gerente, id_plantilla)
 *
 * Nota: requiere permisos de owner en Neon — ejecutar manualmente si falla.
 * SQL equivalente:
 *   DROP INDEX IF EXISTS idx_drafts_gerente;
 *   CREATE UNIQUE INDEX drafts_gerente_plantilla_unique
 *     ON drafts_programacion (id_gerente, id_plantilla);
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_drafts_gerente');
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS drafts_gerente_plantilla_unique
            ON drafts_programacion (id_gerente, id_plantilla)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS drafts_gerente_plantilla_unique');
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_drafts_gerente
            ON drafts_programacion (id_gerente)
        ');
    }
};
