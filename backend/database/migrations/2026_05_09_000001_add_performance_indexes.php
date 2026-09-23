<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Cubre AgendaEspacioController PASO 1 y ReservacionController store()
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_reservas_espacio_fecha_estatus
            ON reservaciones_on_demand (id_espacio, fecha_reserva, estatus_operativo)');

        // Cubre AgendaEspacioController PASO 4a
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_reservas_socio_fecha
            ON reservaciones_on_demand (id_socio_titular, fecha_reserva)');

        // Cubre AgendaEspacioController PASO 2 y ReservacionController conflictoSesion
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_sesiones_fecha_estatus
            ON sesiones_activas (fecha_sesion, estatus_sesion)');

        // Cubre AgendaEspacioController PASO 3 y 4c
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_encuentros_espacio_fecha
            ON encuentros_torneo (id_espacio, fecha_hora_inicio)');

        // Cubre AgendaEspacioController PASO 4b
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_inscripciones_usuario_estatus
            ON inscripciones_clases (id_usuario, estatus_inscripcion)');

        // Cubre LudotecaController y LudotecaStatusController
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ludoteca_hora_ingreso_estatus
            ON registros_ludoteca (hora_ingreso, estatus_ludoteca)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_reservas_espacio_fecha_estatus');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_reservas_socio_fecha');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_sesiones_fecha_estatus');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_encuentros_espacio_fecha');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_inscripciones_usuario_estatus');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_ludoteca_hora_ingreso_estatus');
    }
};
