<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SolicitudAmistadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // tipo: SOLICITUD_ENVIADA | SOLICITUD_ACEPTADA | SOLICITUD_RECHAZADA
    public function __construct(
        public readonly string $tipo,
        public readonly string $nombre_remitente,
        public readonly int    $id_remitente,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $mensaje = match ($this->tipo) {
            'SOLICITUD_ENVIADA'   => "{$this->nombre_remitente} te ha enviado una solicitud de amistad.",
            'SOLICITUD_ACEPTADA'  => "{$this->nombre_remitente} ha aceptado tu solicitud de amistad.",
            'SOLICITUD_RECHAZADA' => "{$this->nombre_remitente} ha rechazado tu solicitud de amistad.",
            default               => "Tienes una actualización de amistad de {$this->nombre_remitente}.",
        };

        return [
            'tipo'            => $this->tipo,
            'mensaje'         => $mensaje,
            'id_remitente'    => $this->id_remitente,
            'nombre_remitente' => $this->nombre_remitente,
        ];
    }
}
