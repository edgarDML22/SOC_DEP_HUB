<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InvitacionEquipoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $nombre_capitan,
        public readonly int    $id_capitan,
        public readonly string $nombre_equipo,
        public readonly int    $id_equipo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'          => 'INVITACION_EQUIPO',
            'mensaje'       => "{$this->nombre_capitan} te ha invitado a unirte al equipo \"{$this->nombre_equipo}\".",
            'id_capitan'    => $this->id_capitan,
            'nombre_capitan' => $this->nombre_capitan,
            'id_equipo'     => $this->id_equipo,
            'nombre_equipo' => $this->nombre_equipo,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitación a equipo de torneo')
            ->line("{$this->nombre_capitan} te ha invitado a unirte al equipo \"{$this->nombre_equipo}\".")
            ->line('Ingresa a la app para aceptar o rechazar la invitación.');
    }
}
