<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    // Evita que Laravel envuelva la migración en una transacción,
    // ya que CREATE INDEX CONCURRENTLY no puede correr dentro de una.
    public $withinTransaction = false;

    public function up(): void
    {
        // Cubre la búsqueda de Sanctum en cada request autenticado
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pat_token
            ON personal_access_tokens (token)');

        // Cubre limpieza por usuario (sanctum:prune-expired y logout)
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pat_tokenable
            ON personal_access_tokens (tokenable_type, tokenable_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_pat_token');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_pat_tokenable');
    }
};
