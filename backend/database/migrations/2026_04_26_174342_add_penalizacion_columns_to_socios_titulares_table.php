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
        // Agregar la columna fecha_fin_penalizacion si no existe
        if (!Schema::hasColumn('socios_titulares', 'fecha_fin_penalizacion')) {
            Schema::table('socios_titulares', function (Blueprint $table) {
                $table->timestamp('fecha_fin_penalizacion')->nullable()->after('contador_no_shows');
            });
        }

        // Actualizar el enum para incluir PENALIZADO (en PostgreSQL se actualiza el tipo)
        DB::statement("ALTER TABLE socios_titulares DROP CONSTRAINT IF EXISTS socios_titulares_estatus_cuenta_check");
        DB::statement("ALTER TABLE socios_titulares ADD CONSTRAINT socios_titulares_estatus_cuenta_check CHECK (estatus_cuenta IN ('AL_CORRIENTE', 'MOROSO', 'PENALIZADO', 'SUSPENDIDO'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->dropColumn('fecha_fin_penalizacion');
        });

        DB::statement("ALTER TABLE socios_titulares DROP CONSTRAINT IF EXISTS socios_titulares_estatus_cuenta_check");
        DB::statement("ALTER TABLE socios_titulares ADD CONSTRAINT socios_titulares_estatus_cuenta_check CHECK (estatus_cuenta IN ('AL_CORRIENTE', 'MOROSO', 'SUSPENDIDO'))");
    }
};
