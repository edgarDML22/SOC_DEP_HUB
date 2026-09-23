<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Las columnas requiere_inscripcion y dia_semana fueron creadas manualmente
        // en pgAdmin con permisos de superusuario:
        //
        //   ALTER TABLE sesiones_activas
        //       ADD COLUMN IF NOT EXISTS requiere_inscripcion BOOLEAN NOT NULL DEFAULT FALSE;
        //
        //   ALTER TABLE sesiones_activas
        //       ADD COLUMN IF NOT EXISTS dia_semana enum_dia_semana NULL;
        //
        // Este migration solo ejecuta el backfill de datos desde actividades_plantilla
        // para todos los registros históricos existentes.

        DB::statement('
            UPDATE sesiones_activas sa
            SET
                requiere_inscripcion = ap.requiere_inscripcion,
                dia_semana           = ap.dia_semana
            FROM actividades_plantilla ap
            WHERE sa.id_actividad_plantilla = ap.id_actividad_plantilla
        ');
    }

    public function down(): void
    {
        // Las columnas deben eliminarse manualmente en pgAdmin si se requiere rollback:
        //   ALTER TABLE sesiones_activas DROP COLUMN IF EXISTS requiere_inscripcion;
        //   ALTER TABLE sesiones_activas DROP COLUMN IF EXISTS dia_semana;
    }
};
