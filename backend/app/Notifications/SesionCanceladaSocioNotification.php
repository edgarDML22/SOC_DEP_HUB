<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SesionCanceladaSocioNotification extends Notification
{

    // Datos planos: evita re-serializar el modelo Eloquent y sus relaciones
    public function __construct(
        public readonly int     $idSesion,
        public readonly ?string $fechaSesion,
        public readonly ?string $disciplina,
        public readonly ?string $instructor,
        public readonly ?string $espacio,
        public readonly ?string $horaInicio,
        public readonly ?string $horaFin,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'         => 'SESION_CANCELADA',
            'id_sesion'    => $this->idSesion,
            'fecha_sesion' => $this->fechaSesion,
            'disciplina'   => $this->disciplina,
            'instructor'   => $this->instructor,
            'espacio'      => $this->espacio,
            'hora_inicio'  => $this->horaInicio,
            'hora_fin'     => $this->horaFin,
            'mensaje'      => 'Una sesión a la que estabas inscrito ha sido cancelada.',
        ];
    }
}
