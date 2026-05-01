<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categoria')->nullable()->after('nombre_disciplina');
            $table->foreign('id_categoria')->references('id')->on('categorias_disciplinas')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropForeign(['id_categoria']);
            $table->dropColumn('id_categoria');
        });
    }
};
