<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En PostgreSQL, si la columna usa un TYPE ENUM, debemos añadir los valores al tipo
        // Nota: ALTER TYPE ADD VALUE no puede ejecutarse dentro de una transacción en algunas versiones de Postgres
        try {
            DB::statement("ALTER TYPE enum_estatus_cuenta ADD VALUE IF NOT EXISTS 'PENALIZADO'");
            DB::statement("ALTER TYPE enum_estatus_cuenta ADD VALUE IF NOT EXISTS 'PENALIZADO_AMBOS'");
            DB::statement("ALTER TYPE enum_estatus_cuenta ADD VALUE IF NOT EXISTS 'PENALIZADO_LUDOTECA'");
            DB::statement("ALTER TYPE enum_estatus_cuenta ADD VALUE IF NOT EXISTS 'PENALIZADO_RESERVA'");
        } catch (\Exception $e) {
            // Si falla porque ya existen o no es un ENUM, continuamos con el CHECK constraint
        }

        // Actualizar el enum para incluir los nuevos tipos de penalización
        DB::statement("ALTER TABLE socios_titulares DROP CONSTRAINT IF EXISTS socios_titulares_estatus_cuenta_check");
        DB::statement("ALTER TABLE socios_titulares ADD CONSTRAINT socios_titulares_estatus_cuenta_check 
            CHECK (estatus_cuenta IN ('AL_CORRIENTE', 'MOROSO', 'PENALIZADO', 'PENALIZADO_AMBOS', 'PENALIZADO_LUDOTECA', 'PENALIZADO_RESERVA', 'SUSPENDIDO'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE socios_titulares DROP CONSTRAINT IF EXISTS socios_titulares_estatus_cuenta_check");
        DB::statement("ALTER TABLE socios_titulares ADD CONSTRAINT socios_titulares_estatus_cuenta_check 
            CHECK (estatus_cuenta IN ('AL_CORRIENTE', 'MOROSO', 'PENALIZADO', 'SUSPENDIDO'))");
    }
};
