<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('actividades_plantilla', function (Blueprint $table) {
            $table->integer('id_instructor_original')->nullable();
            
            // Si el motor soporta FKs
            $table->foreign('id_instructor_original')
                  ->references('id_instructor')
                  ->on('instructores')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actividades_plantilla', function (Blueprint $table) {
            $table->dropForeign(['id_instructor_original']);
            $table->dropColumn('id_instructor_original');
        });
    }
};
