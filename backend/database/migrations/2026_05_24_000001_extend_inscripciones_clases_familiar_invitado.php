<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SDH-308 v2 — Inscripción de familiares e invitados.
 *
 * Añade a inscripciones_clases:
 *   - id_miembro_familiar  → FK nullable a miembros_familiares.id_miembro
 *   - id_pase_invitado     → FK nullable a pases_diarios.id_pase
 *
 * Ambas columnas son nullable: una inscripción normal (socio_titular) no
 * las usa; solo se rellenan cuando el tipo_usuario es 'miembro_familiar'
 * o 'invitado', respectivamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            // FK a miembro familiar
            $table->unsignedBigInteger('id_miembro_familiar')
                  ->nullable()
                  ->after('id_usuario')
                  ->comment('FK a miembros_familiares.id_miembro — solo para tipo_usuario = miembro_familiar');

            $table->foreign('id_miembro_familiar')
                  ->references('id_miembro')
                  ->on('miembros_familiares')
                  ->nullOnDelete();

            // FK al pase diario del invitado
            $table->unsignedBigInteger('id_pase_invitado')
                  ->nullable()
                  ->after('id_miembro_familiar')
                  ->comment('FK a pases_diarios.id_pase — solo para tipo_usuario = invitado');

            $table->foreign('id_pase_invitado')
                  ->references('id_pase')
                  ->on('pases_diarios')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            $table->dropForeign(['id_miembro_familiar']);
            $table->dropForeign(['id_pase_invitado']);
            $table->dropColumn(['id_miembro_familiar', 'id_pase_invitado']);
        });
    }
};
