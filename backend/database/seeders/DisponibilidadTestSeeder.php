<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use App\Models\WorkshopBase;
use App\Models\WorkshopSession;
use App\Models\MeetingRoom;
use App\Models\RoomReservation;
use App\Models\ActividadPlantilla;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use App\Models\SesionActiva;

class DisponibilidadTestSeeder extends Seeder
{
    public function run(): void
    {
        $fechaPrueba = now()->toDateString();

        // ---------------------------------------------------------
        // FLUJO B: Automatizar Plantillas a Sesiones
        // Concepto: Colecciones y método each()
        // ---------------------------------------------------------
        $actividadesPlantilla = ActividadPlantilla::where('estatus', 'ACTIVE')->get();

        $actividadesPlantilla->each(function ($plantilla) use ($fechaPrueba) {
            SesionActiva::create([
                'id_actividad_plantilla' => $plantilla->id,
                'fecha_sesion'     => $fechaPrueba,
                'id_instructor_sustituto' => 1,
                'estatus_sesion' => "DISPONIBLE",
                'cantidad_inscritos' => 0
            ]);
        });


        // ---------------------------------------------------------
        // FLUJO A: Reservaciones On-Demand
        // Concepto: Extracción de IDs y Factories con Sequence
        // ---------------------------------------------------------
        
        $espaciosOnDemand = EspacioFisico::where('room_type', 'RESERVA_ON_DEMAND')
                                       ->pluck('id') //maybe hay que cambiarlo por espacio
                                       ->toArray();

        if (empty($espaciosOnDemand)) {
            $this->command->warn('No hay espacios On-Demand.');
            return;
        }

        // Usamos el Factory inyectándole la fecha y forzando que el room_id 
        // pertenezca estrictamente a nuestra lista extraída usando Sequence.
        ReservaFactory::factory()
            ->count(3)
            ->state(new Sequence(
                fn ($sequence) => ['id_espacio' => fake()->randomElement($espaciosOnDemand)]
            ))
            ->create([
                'reservation_date' => $fechaPrueba,
            ]);
            // Resto de campos de la tabla reservaciones_on_demand
            // Extraer 10 socios activos de su tabla
    }
}
