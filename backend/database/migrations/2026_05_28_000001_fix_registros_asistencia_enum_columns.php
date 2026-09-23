<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La tabla registros_asistencia fue creada en producción con columnas tipo ENUM
 * (enum_metodo_registro_asistencia, enum_tipo_usuario) en lugar de VARCHAR.
 * Esto causa SQLSTATE 22P02 al insertar valores como 'ESCANER_QR' o 'SOCIO_TITULAR'.
 * Esta migración convierte ambas columnas a TEXT manteniendo los datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE registros_asistencia ALTER COLUMN metodo_registro TYPE TEXT USING metodo_registro::TEXT');
        DB::statement('ALTER TABLE registros_asistencia ALTER COLUMN tipo_usuario TYPE TEXT USING tipo_usuario::TEXT');
    }

    public function down(): void
    {
        // No revertimos: no conocemos los valores exactos del ENUM original
    }
};
