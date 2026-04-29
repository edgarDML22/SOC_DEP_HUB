<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstructorDisciplinaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapa de relaciones extraído del cruce de actividades, instructores y disciplinas.
        // Estructura: [id_instructor => [id_disciplina_1, id_disciplina_2, ...]]
        $asignaciones = [
            7  => [1, 2, 9],    // Andrea Castillo -> Aerobics, Baile, Zumba
            48 => [3, 4],       // Lucia Cardenas -> Barre, Higiene de Columna
            3  => [5],          // María López -> Jazz
            9  => [6, 7],       // Sofía Morales -> Meditacion, Pilates
            11 => [7],          // Paula Reyes -> Pilates
            18 => [8],          // Elena Ruíz -> Yoga
            22 => [9],          // Silvia Navarro -> Zumba
            10 => [10],         // Diego Vargas -> Frontenis
            6  => [11],         // Carlos Mendoza -> Padel
            12 => [12],         // Miguel Sánchez -> Squash
            2  => [13],         // Roberto Díaz -> Tenis
            4  => [13],         // Fernando Torres -> Tenis
            19 => [14],         // Javier Castro -> Basquetbol
            33 => [15, 16],     // Eduardo Montes -> Futbol Adultos, Futbol Infantil
            27 => [17],         // Arturo Paredes -> Voleibol
            26 => [18],         // Valeria Romero -> Acondicionamiento Fisico
            32 => [19],         // Gabriela Rios -> Entrenamiento Funcional
            37 => [20],         // Felipe Bravo -> Gym Instructor
            34 => [20],         // Daniela Vega -> Gym Instructor
            24 => [22],         // Diana Salazar -> Spinning
            45 => [22],         // Clara Medina -> Spinning
            39 => [22],         // Patricia Dominguez -> Spinning
            43 => [22],         // Natalia Flores -> Spinning
            44 => [23],         // Ivan Pineda -> Tae Kwon Do
            49 => [24],         // Karla Rivas -> Gimnasia Olimpica
            50 => [25],         // Lorena Campos -> Natacion
            51 => [25],         // Jimena Orozco -> Natacion
            52 => [25],         // Veronica Mora -> Natacion
            36 => [25],         // Esteban Cruz -> Natacion
            21 => [25],         // Ricardo Nuñez -> Natacion
            47 => [25],         // Brenda Valdes -> Natacion
        ];

        DB::beginTransaction();

        try {
            foreach ($asignaciones as $instructorId => $disciplinas) {
                foreach ($disciplinas as $disciplinaId) {
                    // updateOrInsert evalúa la existencia mediante el primer array. 
                    // Si el registro no existe, lo inserta fusionando ambos arrays.
                    // Si existe, solo actualiza las columnas del segundo array.
                    DB::table('instructor_disciplina')->updateOrInsert(
                        [
                            'id_instructor' => $instructorId,
                            'id_disciplina' => $disciplinaId
                        ]
                        
                    );
                }
            }

            DB::commit();
            $this->command->info('Relaciones instructor_disciplina procesadas exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fallo en InstructorDisciplinaSeeder: ' . $e->getMessage());
            $this->command->error('Error al hacer el seeder. Revisa los logs para más detalles. ' . $e->getMessage());
        }
    }
}