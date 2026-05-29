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
        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->string('foto_perfil')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socios_titulares', function (Blueprint $table) {
            $table->dropColumn('foto_perfil');
        });
    }
};
