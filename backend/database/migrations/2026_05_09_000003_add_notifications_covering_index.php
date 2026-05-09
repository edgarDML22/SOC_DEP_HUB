<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public bool $withinTransaction = false;

    public function up(): void
    {
        // El índice que genera morphs() cubre (notifiable_type, notifiable_id)
        // pero la query ordena por created_at DESC, forzando un filesort.
        // Este índice cubre el filtro Y el orden en una sola pasada de B-tree.
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_notifications_notifiable_created
            ON notifications (notifiable_type, notifiable_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_notifications_notifiable_created');
    }
};
