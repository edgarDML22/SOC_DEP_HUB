<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->boolean('publicada')->default(false)->after('estatus_plantilla');
        });
    }

    public function down(): void
    {
        Schema::table('plantillas_programacion', function (Blueprint $table) {
            $table->dropColumn('publicada');
        });
    }
};
