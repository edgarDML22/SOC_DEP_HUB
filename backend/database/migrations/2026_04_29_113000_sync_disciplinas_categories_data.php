<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\CategoriaDisciplina;
use App\Models\Disciplina;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Obtener todos los valores únicos del ENUM actual en la tabla disciplinas
        // Usamos DB::table para evitar problemas si el modelo aún no está sincronizado
        $categoriasEnum = DB::table('disciplinas')
            ->select('categoria_disciplina')
            ->distinct()
            ->pluck('categoria_disciplina')
            ->filter();

        // 2. Insertar estos valores en la nueva tabla categorias_disciplinas si no existen
        foreach ($categoriasEnum as $nombre) {
            // Corregir el typo 'INFALTIL' si existe
            $nombreLimpio = ($nombre === 'INFALTIL') ? 'INFANTIL' : $nombre;
            
            $categoria = CategoriaDisciplina::firstOrCreate(
                ['nombre_categoria' => $nombreLimpio],
                ['descripcion_categoria' => 'Categoría migrada del sistema anterior.']
            );

            // 3. Actualizar la relación en la tabla disciplinas
            DB::table('disciplinas')
                ->where('categoria_disciplina', $nombre)
                ->update(['id_categoria' => $categoria->id]);
        }
        
        // 4. Si la tabla está vacía, insertar los valores estándar por defecto
        $valoresEstándar = [
            'MENTE_CUERPO', 'DEPORTES_RAQUETA', 'DEPORTES_EQUIPO', 
            'ACONDICIONAMIENTO_FISICO', 'ARTES_MARCIALES', 'GIMNASIA', 'ACUATICO', 'INFANTIL'
        ];
        
        foreach ($valoresEstándar as $valor) {
            CategoriaDisciplina::firstOrCreate(
                ['nombre_categoria' => $valor],
                ['descripcion_categoria' => 'Categoría estándar del sistema.']
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es estrictamente necesario revertir los datos, 
        // pero podríamos limpiar id_categoria si fuera necesario.
        DB::table('disciplinas')->update(['id_categoria' => null]);
    }
};
