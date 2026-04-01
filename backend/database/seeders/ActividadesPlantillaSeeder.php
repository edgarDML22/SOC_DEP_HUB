<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActividadPlantilla; // Tu nuevo modelo pivote
use App\Models\Disciplina;
use App\Models\EspacioFisico;
use App\Models\Instructor;
use App\Models\PlantillaProgramacion;
use Illuminate\Support\Facades\File;

class ActividadesPlantillaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtenemos el ID de la plantilla que acabamos de crear en el Seeder anterior
        // Usamos first() para traer el registro y luego sacamos su ID
        $plantillaOficial = PlantillaProgramacion::where('nombre_plantilla', 'Programación Base Disciplinas 2026')->first();
        
        if (!$plantillaOficial) {
            $this->command->error("¡Alto! No existe la plantilla. Corre el PlantillasProgramacionSeeder primero.");
            return;
        }

        $idPlantilla = $plantillaOficial->id_plantilla; 
        $rutaArchivo = database_path('data/actividades_plantilla.csv');
        $archivoAbierto = fopen($rutaArchivo, 'r');
        $isFirstRow = true;

        while (($fila = fgetcsv($archivoAbierto, 1000, ',')) !== false) {
            if ($isFirstRow) {
                $isFirstRow = false;
                continue;
            }

            // Textos del CSV
            $disciplina = trim($fila[0]);
            $nombre_espacio   = trim($fila[1]);
            // ... los demás campos ...

            // --- LA MAGIA DE ELOQUENT (Búsqueda por texto para sacar ID) ---
            $id_disciplina = Disciplina::where('nombre_disciplina', $nombre_disciplina_csv)->value('id_disciplina');
            $id_espacio    = EspacioFisico::where('nombre_espacio', $nombre_espacio_csv)->value('id_espacio');
            
            // Para el instructor, si no existe, dijimos que forzábamos el ID 1
            $id_instructor = Instructor::where('nombre', trim($fila[2]))->value('id_instructor') ?? 1;

            // Validamos que existan en catálogo
            if (!$id_disciplina || !$id_espacio) {
                $this->command->warn("Fila ignorada: No se encontró disciplina/espacio -> " . $nombre_disciplina_csv);
                continue;
            }

            // Insertamos el detalle asociándolo a la plantilla maestra
            ActividadPlantilla::create([
                'id_plantilla'  => $idPlantilla,     // <--- Aquí conectamos el Detalle con el Maestro
                'id_disciplina' => $id_disciplina,
                'id_espacio'    => $id_espacio,
                'id_instructor' => $id_instructor,
                'dia_semana'    => trim($fila[3]),
                'hora_inicio'   => trim($fila[4]),
                'hora_fin'      => trim($fila[5]),
                'cupo_maximo'   => trim($fila[6]),
                // 'estatus' => trim($fila[7]), (Si lo pusiste en tu tabla)
            ]);
        }

        fclose($archivoAbierto);
        $this->command->info("¡Actividades de la plantilla importadas con éxito!");
    }
}