<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('espacios_fisicos', function (Blueprint $table) {
            $table->boolean('es_reserva_on_demand')->default(false);
            $table->boolean('es_clase_programada')->default(false);
            $table->boolean('es_uso_libre')->default(false);
        });

        // Migrate data
        DB::statement("UPDATE espacios_fisicos SET es_reserva_on_demand = true WHERE tipo_espacio = 'RESERVA_ON_DEMAND'");
        DB::statement("UPDATE espacios_fisicos SET es_clase_programada = true WHERE tipo_espacio = 'CLASE_PROGRAMADA'");
        DB::statement("UPDATE espacios_fisicos SET es_uso_libre = true WHERE tipo_espacio = 'USO_LIBRE'");
    }

    public function down()
    {
        Schema::table('espacios_fisicos', function (Blueprint $table) {
            $table->dropColumn(['es_reserva_on_demand', 'es_clase_programada', 'es_uso_libre']);
        });
    }
};
