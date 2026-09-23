<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categorias_disciplinas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_categoria')->unique();
            $table->text('descripcion_categoria')->nullable();
            $table->timestamps();
        });

        // Insert initial categories
        DB::table('categorias_disciplinas')->insert([
            ['nombre_categoria' => 'MENTE_CUERPO'],
            ['nombre_categoria' => 'DEPORTES_RAQUETA'],
            ['nombre_categoria' => 'DEPORTES_EQUIPO'],
            ['nombre_categoria' => 'ACONDICIONAMIENTO_FISICO'],
            ['nombre_categoria' => 'ARTES_MARCIALES'],
            ['nombre_categoria' => 'GIMNASIA'],
            ['nombre_categoria' => 'ACUATICO'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('categorias_disciplinas');
    }
};
