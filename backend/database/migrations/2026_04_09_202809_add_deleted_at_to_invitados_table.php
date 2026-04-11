<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invitados', function (Blueprint $table) {
            // Esto agrega mágicamente la columna 'deleted_at'
            $table->softDeletes(); 
        });
    }

    public function down()
    {
        Schema::table('invitados', function (Blueprint $table) {
            // Por si algún día quieres revertir este cambio
            $table->dropSoftDeletes(); 
        });
    }
};