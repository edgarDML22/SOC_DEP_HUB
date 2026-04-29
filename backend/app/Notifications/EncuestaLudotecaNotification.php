<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EncuestaLudotecaNotification extends Notification
{
    public $idHistorial;

    public function __construct($idHistorial)
    {
        $this->idHistorial = $idHistorial;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Encuesta de satisfacción - Ludoteca')
            ->line('Gracias por usar el servicio de ludoteca.')
            ->line('Ayúdanos contestando esta encuesta.')
            ->action(
                'Responder encuesta',
                "http://localhost:5173/ludoteca/encuesta/{$this->idHistorial}"
            );
    }
}