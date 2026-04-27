<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EncuestaLudotecaNotification extends Notification
{
    public $idRegistro;

    public function __construct($idRegistro)
    {
        $this->idRegistro = $idRegistro;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Encuesta de satisfacción Ludoteca')
            ->line('Gracias por utilizar nuestro servicio.')
            ->line('Ayúdanos contestando esta encuesta.')
            ->action(
                'Responder encuesta',
                "http://localhost:3000/encuesta/{$this->idRegistro}"
            );
    }
}