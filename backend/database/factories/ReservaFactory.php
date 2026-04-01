<?php

use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    public function definition(): array
    {
        $horaInicioInt = fake()->numberBetween(8, 20);
        
        // Formateamos a cadena de tiempo (ej. "14:00:00")
        $horaInicio = sprintf('%02d:00:00', $horaInicioInt);
        $horaFin    = sprintf('%02d:00:00', $horaInicioInt + 2);

        return [
            // 'room_id' y 'reservation_date' vienen inyectados desde el Seeder
            'start_time' => $horaInicio,
            'end_time'   => $horaFin,
            'status'     => 'ACTIVA',
        ];
    }
}
