<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Instructor;

class InstructoresSeeder extends Seeder
{
    public function run(): void
    {
        $rutaArchivo = database_path('data/instructores.csv');

        if (!File::exists($rutaArchivo)) {
            $this->command->error("No se encontró el archivo: {$rutaArchivo}");
            return;
        }

        $archivoAbierto = fopen($rutaArchivo, 'r');
        $esPrimeraFila = true;

        while (($fila = fgetcsv($archivoAbierto, 1000, ',')) !== false) {
            
            if ($esPrimeraFila) {
                $esPrimeraFila = false;
                continue;
            }

            $nombre   = trim($fila[1]);
            
            $instructorExiste = Instructor::where('nombre_completo', $nombre)->exists();

            if ($instructorExiste) {
                $this->command->warn("Saltando a {$nombre}... ya existe en la BD");
                continue; 
            }

            $telefono = trim($fila[2]);
            $correo   = trim($fila[3]);
            $estatus  = trim($fila[4]);
            
            $fechaContratacion = Carbon::createFromFormat('d/m/Y', trim($fila[5]))->format('Y-m-d');
            $fechaNacimiento   = Carbon::createFromFormat('d/m/Y', trim($fila[6]))->format('Y-m-d');

            DB::transaction(function () use ($nombre, $correo, $telefono, $estatus, $fechaContratacion, $fechaNacimiento) {
                
                // 1. Creamos al Instructor en su tabla
                $instructor = Instructor::create([
                    'nombre_completo'             => $nombre,
                    'telefono'           => $telefono,
                    'estatus'            => $estatus,
                    'fecha_afiliacion' => $fechaContratacion,
                    'fecha_nacimiento'   => $fechaNacimiento,
                    'correo_electronico' => $correo
                ]);

                // Obtenemos el ID generado en la tabla instructores
                $idGeneradoInstructor = $instructor->id_instructor;

                // 2. Creamos al Usuario inyectando el ID del instructor en 'user_id'
                User::create([
                    'email'    => $correo,
                    'password' => Hash::make('password'), 
                    'rol'      => 'instructor',
                    'user_id'  => $idGeneradoInstructor
                ]);
            });
        }

        fclose($archivoAbierto);
        $this->command->info("Instructores nuevos insertados.");
    }
}