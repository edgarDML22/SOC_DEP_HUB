<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Las columnas fueron creadas manualmente en pgAdmin con permisos de superusuario.
        // Este migration solo ejecuta el backfill de datos desde actividades_plantilla.
        DB::statement('
            UPDATE sesiones_activas sa
            SET
                id_disciplina           = ap.id_disciplina,
                id_espacio              = ap.id_espacio,
                id_insctructor          = ap.id_instructor,
                cupo_maximo             = ap.cupo_maximo,
                hora_inicio             = ap.hora_inicio,
                hora_fin                = ap.hora_fin
            FROM actividades_plantilla ap
            WHERE sa.id_actividad_plantilla = ap.id_actividad_plantilla
        ');
    }

    public function down(): void
    {
        // Revertir el backfill no es posible de forma segura; las columnas deben
        // eliminarse manualmente en pgAdmin si se requiere rollback completo.
    }
};
