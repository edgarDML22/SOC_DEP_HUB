<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── plantillas_programacion ───────────────────────────────────────────
        DB::statement('CREATE INDEX IF NOT EXISTS idx_plantillas_fechas
            ON plantillas_programacion(fecha_inicio, fecha_fin)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_plantillas_estatus
            ON plantillas_programacion(estatus_plantilla)');

        // ── inscripciones_clases ──────────────────────────────────────────────
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inscripciones_sesion
            ON inscripciones_clases(id_sesion)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_inscripciones_usuario
            ON inscripciones_clases(id_usuario)');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_inscripciones_estatus
            ON inscripciones_clases(estatus_inscripcion)');

        // Índice compuesto para el lookup crítico: ¿ya está inscrito?
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inscripciones_lookup
            ON inscripciones_clases(id_usuario, id_sesion, estatus_inscripcion)');

        // Historial del usuario ordenado por fecha
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inscripciones_usuario_fecha
            ON inscripciones_clases(id_usuario, fecha_transaccion)');

        // ── sesiones_activas ──────────────────────────────────────────────────
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sesiones_fecha
            ON sesiones_activas(fecha_sesion)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_plantillas_fechas');
        DB::statement('DROP INDEX IF EXISTS idx_plantillas_estatus');
        DB::statement('DROP INDEX IF EXISTS idx_inscripciones_sesion');
        DB::statement('DROP INDEX IF EXISTS idx_inscripciones_usuario');
        DB::statement('DROP INDEX IF EXISTS idx_inscripciones_estatus');
        DB::statement('DROP INDEX IF EXISTS idx_inscripciones_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_inscripciones_usuario_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_sesiones_fecha');
    }
};
