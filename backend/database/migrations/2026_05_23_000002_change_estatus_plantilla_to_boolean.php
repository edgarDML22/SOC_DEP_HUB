<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->dropColumn('estatus_plantilla');
        });

        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->boolean('estatus_plantilla')->default(false)->after('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->dropColumn('estatus_plantilla');
        });

        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->string('estatus_plantilla')->default('INACTIVO')->after('fecha_fin');
        });
    }
};
