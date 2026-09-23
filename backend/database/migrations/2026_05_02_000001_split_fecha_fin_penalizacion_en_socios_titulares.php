<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->timestamp('fecha_fin_penalizacion_ludoteca')->nullable()->after('fecha_fin_penalizacion');
            $table->timestamp('fecha_fin_penalizacion_reserva')->nullable()->after('fecha_fin_penalizacion_ludoteca');
        });

        // Migrar datos existentes: si había una penalización activa, la copiamos
        // a la columna correcta según el estatus actual
        DB::statement("
            UPDATE socios_titulares
            SET fecha_fin_penalizacion_ludoteca = fecha_fin_penalizacion
            WHERE estatus_penalizacion IN ('PENALIZADO_LUDOTECA', 'PENALIZADO_AMBOS')
              AND fecha_fin_penalizacion IS NOT NULL
        ");

        DB::statement("
            UPDATE socios_titulares
            SET fecha_fin_penalizacion_reserva = fecha_fin_penalizacion
            WHERE estatus_penalizacion IN ('PENALIZADO_RESERVA', 'PENALIZADO_AMBOS')
              AND fecha_fin_penalizacion IS NOT NULL
        ");

        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->dropColumn('fecha_fin_penalizacion');
        });
    }

    public function down(): void
    {
        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->timestamp('fecha_fin_penalizacion')->nullable()->after('retrasos_ludoteca');
        });

        // Restaurar: usar la fecha más próxima entre las dos columnas
        DB::statement("
            UPDATE socios_titulares
            SET fecha_fin_penalizacion = GREATEST(
                COALESCE(fecha_fin_penalizacion_ludoteca, '1970-01-01'),
                COALESCE(fecha_fin_penalizacion_reserva,  '1970-01-01')
            )
            WHERE fecha_fin_penalizacion_ludoteca IS NOT NULL
               OR fecha_fin_penalizacion_reserva  IS NOT NULL
        ");

        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->dropColumn(['fecha_fin_penalizacion_ludoteca', 'fecha_fin_penalizacion_reserva']);
        });
    }
};
