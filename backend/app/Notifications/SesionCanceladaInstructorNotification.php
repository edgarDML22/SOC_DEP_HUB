<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SesionCanceladaInstructorNotification extends Notification
{

    public function __construct(
        public readonly int     $idSesion,
        public readonly ?string $fechaSesion,
        public readonly ?string $disciplina,
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
            'tipo'         => 'SESION_CANCELADA_INSTRUCTOR',
            'id_sesion'    => $this->idSesion,
            'fecha_sesion' => $this->fechaSesion,
            'disciplina'   => $this->disciplina,
            'espacio'      => $this->espacio,
            'hora_inicio'  => $this->horaInicio,
            'hora_fin'     => $this->horaFin,
            'mensaje'      => 'Una de tus sesiones ha sido cancelada por la administración.',
        ];
    }
}
