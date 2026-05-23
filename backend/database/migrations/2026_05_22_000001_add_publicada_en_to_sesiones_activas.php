<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesiones_activas', function (Blueprint $table) {
            // Marca cuándo fue publicada la sesión.
            // Se usa para la ventana de 30 minutos de despublicación sin restricciones.
            $table->timestamp('fecha_publicacion')->nullable()->after('cantidad_inscritos');
        });
    }

    public function down(): void
    {
        Schema::table('sesiones_activas', function (Blueprint $table) {
            $table->dropColumn('fecha_publicacion');
        });
    }
};
