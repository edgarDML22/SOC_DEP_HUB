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
        Schema::table('reservaciones_on_demand', function (Blueprint $table) {
            $table->jsonb('acompanantes_draft')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservaciones_on_demand', function (Blueprint $table) {
            $table->dropColumn('acompanantes_draft');
        });
    }
};
