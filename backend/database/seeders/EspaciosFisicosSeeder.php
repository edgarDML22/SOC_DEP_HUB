<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EspacioFisico;
use Illuminate\Support\Facades\File;

class EspaciosFisicosSeeder extends Seeder
{
    public function run(): void
    {
        $route = database_path('data/espacios_fisicos.csv');

        if(!File::exists($route)) {
            $this->command->error("No se encontró el archivo: {$route}");
            return;
        }

        $openFile = fopen($route, 'r');
        $isFirstRow = true;

        while (($row = fgetcsv($openFile, 1000, ',')) !== false) {
            
            if ($isFirstRow) {
                $isFirstRow = false;
                continue;
            }

            $nombre_espacio = $row[0];
            $tipo_espacio  = $row[1];
            $capacidad_maxima = $row[2];
            $estatus = $row[3];

            EspacioFisico::updateOrCreate(
                ['nombre_espacio' => $nombre_espacio], 
                [
                    'tipo_espacio' => $tipo_espacio,
                    'capacidad_maxima' => $capacidad_maxima,
                    'estatus'=> $estatus
                ]
            );
        }

        fclose($openFile);
        

    }
    
}
