<?php

namespace Database\Seeders;

use App\Models\Disciplina;
use App\Models\EspacioFisico;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EspacioDisciplinaSeeder extends Seeder
{

    public function run(): void
    {
        DB::transaction(function () {
            // pluck crea un arreglo asociativo: ['Tenis' => 1, 'Pádel' => 2, ...]
            $disciplinasBD = Disciplina::pluck('id_disciplina', 'nombre_disciplina');

            // Traemos TODOS los espacios en un solo viaje
            $espacios = EspacioFisico::all();

            // 2. EL DICCIONARIO DE REGLAS (Aquí configuras todo en un solo lugar)
            // Llave: Palabra clave que buscará en el nombre del espacio
            // Valor: Arreglo de disciplinas que le corresponden
            $hashMap = [
                //esapacio => disciplina
                'Tenis'     => ['Tenis'],
                'Padel'     => ['Padel'],
                'Fronton'   => ['Frontenis'],
                'Squash'    => ['Squash'],
                'Futbol'    => ['Futbol Adultos', 'Futbol Infantil'],
                'Multiusos' => ['Basquetbol', 'Voleibol'],
                'Alberca'   => ['Natacion']
            ];

            foreach ($espacios as $espacio) {
                $idsAsignar = [];

                // Revisamos cada regla de nuestro diccionario
                foreach ($hashMap as $palabraClave => $disciplinasRequeridas) {
                    // Si el nombre del espacio contiene la palabra clave (ej. "Cancha de Tenis 1" contiene "Tenis")
                    if (stripos($espacio->nombre_espacio, $palabraClave) !== false) {
                        // Buscamos los IDs de esas disciplinas en nuestra memoria
                        foreach ($disciplinasRequeridas as $nombreDisciplina) {
                            if (isset($disciplinasBD[$nombreDisciplina])) {
                                $idsAsignar[] = $disciplinasBD[$nombreDisciplina];
                            }
                        }
                    }
                }
                if (!empty($idsAsignar)) {
                    $espacio->disciplinas()->sync($idsAsignar); //INSERT into espacio_disciplina
                }
                if (empty($idsAsignar) && $palabraClave === 'Futbol') {
                    dump("No encontré disciplinas para: " . $espacio->nombre_espacio);
                } else {
                    dump("ÉXITO: Espacio '" . $espacio->nombre_espacio . "' recibirá los IDs: ", $idsAsignar);
                }
            }
        });
    }
}
