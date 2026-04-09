<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reservacion;

class ReservacionFactory extends Factory
{
    protected $model = Reservacion::class;
    public function definition(): array
    {
        $horaInicioInt = fake()->numberBetween(8, 20);
        
        // Formateamos a cadena de tiempo (ej. "14:00:00")
        $horaInicio = sprintf('%02d:00:00', $horaInicioInt);
        $horaFin    = sprintf('%02d:00:00', $horaInicioInt + 2);

        return [
            'hora_inicio' => $horaInicio,
            'hora_fin'   => $horaFin,
            'estatus_operativo'     => 'ACTIVA',
        ];
    }
}
