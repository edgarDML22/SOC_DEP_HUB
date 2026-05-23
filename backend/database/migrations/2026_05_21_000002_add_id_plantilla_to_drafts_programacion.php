<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drafts_programacion', function (Blueprint $table) {
            $table->unsignedBigInteger('id_plantilla')->nullable()->after('id_gerente');
            $table->foreign('id_plantilla')
                  ->references('id_plantilla')
                  ->on('plantillas_programacion')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drafts_programacion', function (Blueprint $table) {
            $table->dropForeign(['id_plantilla']);
            $table->dropColumn('id_plantilla');
        });
    }
};
