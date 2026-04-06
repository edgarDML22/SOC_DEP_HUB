<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disciplina;
use Illuminate\Support\Facades\File;

class DisciplinasSeeder extends Seeder
{

    public function run(): void
    {
        $route = database_path("data/disciplinas.csv");

        if (!File::exists($route)) {
            $this->command->error("No se encontró el archivo: {$route}");
            return;
        }

        $openFile = fopen($route, "r");
        $isFirstRow = true;

        while (($row = fgetcsv($openFile, 1000, ',')) !== false) {
            if ($isFirstRow) {
                $isFirstRow = false;
                continue;
            }

            $nombre_disciplina = $row[0];
            $categoria_disciplina  = $row[1];
            $requiere_instructor = $row[2];
            $estatus = $row[3];

            Disciplina::updateOrCreate(
                ['nombre_disciplina' => $nombre_disciplina],
                [
                    'categoria_disciplina' => $categoria_disciplina,
                    'requiere_instructor' => $requiere_instructor,
                    'estatus' => $estatus
                ]
            );
        }

        fclose($openFile);
    }
}
