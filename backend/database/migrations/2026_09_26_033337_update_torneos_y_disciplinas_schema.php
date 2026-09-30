<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::transaction(function () {
                // 1. Limpieza Temporal de Datos Previos
                DB::table('torneos')->where('modalidad', 'MIXTO')->update(['modalidad' => 'INDIVIDUAL']);
                // Ya no actualizamos 'id_categoria' porque es entero y no usa el enum.
                // 2. Modificación de modalidad_enum
                DB::statement("ALTER TYPE modalidad_enum RENAME TO modalidad_enum_old;");
                DB::statement("CREATE TYPE modalidad_enum AS ENUM('INDIVIDUAL', 'PAREJAS', 'EQUIPO');");
                DB::statement("ALTER TABLE torneos ALTER COLUMN modalidad TYPE modalidad_enum USING modalidad::text::modalidad_enum;");
                DB::statement("DROP TYPE modalidad_enum_old;");
                
                // 3. Modificación de enum_categoria_torneo
                DB::statement("ALTER TYPE enum_categoria_torneo RENAME TO enum_categoria_torneo_old;");
                DB::statement("CREATE TYPE enum_categoria_torneo AS ENUM('INFANTIL', 'JUVENIL', 'LIBRE');");
                // Como no hay columnas usándolo, solo eliminamos el viejo
                DB::statement("DROP TYPE enum_categoria_torneo_old;");
                
                // 4. Ajustes vía Schema Builder
                Schema::table('torneos', function (Blueprint $table) {
                    if (!Schema::hasColumn('torneos', 'min_integrantes_equipo')) {
                        $table->integer('min_integrantes_equipo')->nullable();
                    }
                    if (!Schema::hasColumn('torneos', 'max_integrantes_equipo')) {
                        $table->integer('max_integrantes_equipo')->nullable();
                    }
                });
                
                Schema::table('disciplinas', function (Blueprint $table) {
                    if (Schema::hasColumn('disciplinas', 'id_categoria') && !Schema::hasColumn('disciplinas', 'id_naturaleza')) {
                        $table->renameColumn('id_categoria', 'id_naturaleza');
                    }
                    if (!Schema::hasColumn('disciplinas', 'aplica_para_torneos')) {
                        $table->boolean('aplica_para_torneos')->default(false);
                    }
                });
                
                Schema::table('actividades_plantilla', function (Blueprint $table) {
                    if (!Schema::hasColumn('actividades_plantilla', 'categoria_sesion')) {
                        $table->string('categoria_sesion')->nullable();
                    }
                });
            });
        } catch (\Exception $e) {
            Log::error('Error en migración update_torneos_y_disciplinas_schema: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::transaction(function () {
                // 1. Revertir Schema Builder
                Schema::table('actividades_plantilla', function (Blueprint $table) {
                    $table->dropColumn('categoria_sesion');
                });
                
                Schema::table('disciplinas', function (Blueprint $table) {
                    $table->dropColumn('aplica_para_torneos');
                    $table->renameColumn('id_naturaleza', 'id_categoria');
                });
                
                Schema::table('torneos', function (Blueprint $table) {
                    $table->dropColumn(['min_integrantes_equipo', 'max_integrantes_equipo']);
                });
                
                // 2. Restaurar enum_categoria_torneo (con MASTER)
                DB::statement("ALTER TYPE enum_categoria_torneo RENAME TO enum_categoria_torneo_old;");
                DB::statement("CREATE TYPE enum_categoria_torneo AS ENUM('INFANTIL', 'JUVENIL', 'LIBRE', 'MASTER');");
                DB::statement("DROP TYPE enum_categoria_torneo_old;");
                
                // 3. Restaurar modalidad_enum (con MIXTO)
                DB::statement("ALTER TYPE modalidad_enum RENAME TO modalidad_enum_old;");
                DB::statement("CREATE TYPE modalidad_enum AS ENUM('INDIVIDUAL', 'PAREJAS', 'MIXTO');");
                DB::statement("ALTER TABLE torneos ALTER COLUMN modalidad TYPE modalidad_enum USING modalidad::text::modalidad_enum;");
                DB::statement("DROP TYPE modalidad_enum_old;");
            });
        } catch (\Exception $e) {
            Log::error('Error en rollback update_torneos_y_disciplinas_schema: ' . $e->getMessage());
            throw $e;
        }
    }
};
