<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de asistencias — 100% PostgreSQL.
 * Reemplaza la colección MongoDB RegistroAsistecia (eliminada).
 *
 * Usar: php artisan migrate --pretend  para verificar sin ejecutar
 * si la tabla ya existe en producción. Si ya existe, esta migración
 * es idempotente gracias a createIfNotExists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registros_asistencia')) {
            return; // tabla ya existe en producción — no recriar
        }

        Schema::create('registros_asistencia', function (Blueprint $table) {
            $table->bigIncrements('id_registro');

            $table->unsignedBigInteger('id_sesion');
            $table->unsignedBigInteger('id_usuario');
            $table->string('tipo_usuario', 30);   // SOCIO | FAMILIAR | EXTERNO
            $table->string('metodo_registro', 20); // ingreso | cierre
            $table->boolean('asistencia')->default(true);
            $table->timestampTz('fecha_hora_registro')->useCurrent();

            $table->foreign('id_sesion')
                  ->references('id_sesion')
                  ->on('sesiones_activas')
                  ->onDelete('cascade');

            // Índice compuesto para consultas de historial y deduplicación
            $table->index(['id_sesion', 'id_usuario', 'metodo_registro'], 'idx_asistencia_sesion_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_asistencia');
    }
};
