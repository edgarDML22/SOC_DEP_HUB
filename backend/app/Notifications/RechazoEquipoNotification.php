<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RechazoEquipoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $nombre_companero,
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
            'tipo'             => 'RECHAZO_EQUIPO',
            'mensaje'          => "{$this->nombre_companero} ha rechazado la invitación al equipo \"{$this->nombre_equipo}\".",
            'nombre_companero' => $this->nombre_companero,
            'id_equipo'        => $this->id_equipo,
            'nombre_equipo'    => $this->nombre_equipo,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitación de equipo rechazada')
            ->line("{$this->nombre_companero} ha rechazado tu invitación al equipo \"{$this->nombre_equipo}\".")
            ->line('Puedes reasignar un nuevo compañero desde la app.');
    }
}
