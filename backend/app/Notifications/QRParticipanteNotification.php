<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

use Illuminate\Support\HtmlString;

class QRParticipanteNotification extends Notification
{
    use Queueable;

    protected $participante;

    public function __construct($participante)
    {
        $this->participante = $participante;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Registro aprobado - SOC-DEP HUB')
            ->greeting("Hola, {$this->participante->nombre_completo}")
            ->line('Nos complace informarte que tu preregistro ha sido **aprobado** y tu inscripción al torneo está confirmada.')
            ->line('Presenta el siguiente código QR en la entrada el día del evento:')
            ->line("![QR Code]({$this->participante->qr_codigo})")
            ->line('¡Te esperamos y mucho éxito en el torneo!')
            ->salutation('Club SOC-DEP HUB');
    }
}