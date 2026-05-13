<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Cubre getDisponibilidadOnDemand(): WHERE es_reserva_on_demand = true
        // Índice parcial: solo indexa filas TRUE (minoría), más compacto que un índice completo.
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_espacios_on_demand
            ON espacios_fisicos (id_espacio, estatus)
            WHERE es_reserva_on_demand = true');

        // Cubre el eager load de disciplinas: ON espacio_disciplina.id_espacio = espacios_fisicos.id_espacio
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_espacio_disciplina_espacio
            ON espacio_disciplina (id_espacio, id_disciplina)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_espacios_on_demand');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_espacio_disciplina_espacio');
    }
};
