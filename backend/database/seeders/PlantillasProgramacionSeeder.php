<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlantillaProgramacion; 

class PlantillasProgramacionSeeder extends Seeder
{
    public function run(): void
    {
        PlantillaProgramacion::updateOrCreate(
            ['nombre_plantilla' => 'Programación Base Disciplinas 2026'], 
            ['estatus_plantilla' => true]
        );

        $this->command->info("Plantilla creada exitosamente.");
    }
}
