<?php

namespace Database\Seeders; // <-- Ojo, mantén esto

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB; // <-- 1. IMPORTAR DB
use App\Models\ActividadPlantilla;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;

class DisponibilidadTestSeeder extends Seeder
{
    public function run(): void
    {
        $fechaPrueba = now()->toDateString();
        
        DB::transaction(function () use ($fechaPrueba) {

            // ---------------------------------------------------------
            // FLUJO B: Automatizar Plantillas a Sesiones
            // ---------------------------------------------------------
            $actividadesPlantilla = ActividadPlantilla::where('estatus', 'ACTIVO')->get();

            $actividadesPlantilla->each(function ($plantilla) use ($fechaPrueba) {
                SesionActiva::create([
                    // Asegúrate de usar el ID correcto aquí (id o id_actividad_plantilla)
                    'id_actividad_plantilla' => $plantilla->id_actividad_plantilla,
                    'fecha_sesion'     => $fechaPrueba,
                    'id_instructor_sustituto' => 1,
                    'estatus_sesion' => "DISPONIBLE",
                    'cantidad_inscritos' => 0
                ]);
            });

            // ---------------------------------------------------------
            // FLUJO A: Reservaciones On-Demand
            // ---------------------------------------------------------
            $espaciosOnDemand = EspacioFisico::where('tipo_espacio', 'RESERVA_ON_DEMAND')
                ->pluck('id_espacio')
                ->toArray();

            if (empty($espaciosOnDemand)) {
                $this->command->warn('No hay espacios On-Demand.');
                return; // Esto detiene el seeder pero hace commit de lo anterior
            }

            $sociosDisponibles = SocioTitular::where('estatus_cuenta', '!=', 'SUSPENDIDO')
                ->take(10)
                ->pluck('id_socio')
                ->toArray();

            if (empty($sociosDisponibles)) {
                $this->command->warn('No hay Socios Disponibles');
                return;
            }

            Reservacion::factory() 
                ->count(3)
                ->state(function () use ($espaciosOnDemand, $sociosDisponibles) {
                    return [
                        'id_espacio' => fake()->randomElement($espaciosOnDemand),
                        'id_socio_titular' => fake()->randomElement($sociosDisponibles)
                    ];
                })
                ->create([
                    'fecha_reserva' => $fechaPrueba
                ]);
        }); // <-- FIN DEL TRANSACTION
    }
}
