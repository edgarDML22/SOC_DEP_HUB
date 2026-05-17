<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PreRegistroRechazadoNotification extends Notification
{
    use Queueable;

    protected $motivo;

    public function __construct($motivo)
    {
        $this->motivo = $motivo;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Pre-registro rechazado')
            ->line('Tu preregistro fue rechazado.')
            ->line('Motivo: ' . $this->motivo);
    }
}