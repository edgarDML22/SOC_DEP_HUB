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

            $nombre_disciplina = trim($fila[0]);
            $nombre_espacio   = trim($fila[1]);
            $nombre_instructor = trim($fila[2]);
            $dia_semana = trim($fila[3]);
            $hora_inicio = trim($fila[4]);
            $hora_fin = trim($fila[5]);
            $cupo_maximo = trim($fila[6]);
            $estatus = trim($fila[7]);


            $id_disciplina = Disciplina::where('nombre_disciplina', $nombre_disciplina)->value('id_disciplina');
            $id_espacio    = EspacioFisico::where('nombre_espacio', $nombre_espacio)->value('id_espacio');
            
            // Para el instructor, si no existe, dijimos que forzábamos el ID 1
            $id_instructor = Instructor::where('nombre_instructor', $nombre_instructor)->value('id_instructor') ?? 1;

            if (!$id_disciplina || !$id_espacio) {
                $this->command->warn("Fila ignorada: No se encontró disciplina/espacio -> " . $nombre_disciplina);
                continue;
            }

            ActividadPlantilla::create([
                'id_plantilla'  => $idPlantilla,     
                'id_disciplina' => $id_disciplina,
                'id_espacio'    => $id_espacio,
                'id_instructor' => $id_instructor,
                'dia_semana'    => $dia_semana,
                'hora_inicio'   => $hora_inicio,
                'hora_fin'      => $hora_fin,
                'cupo_maximo'   => $cupo_maximo,
                'estatus' => $estatus
            ]);
        }

        fclose($archivoAbierto);
        $this->command->info("¡Actividades de la plantilla importadas con éxito!");
    }
}