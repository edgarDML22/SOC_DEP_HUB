<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AlertaRecogidaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $minutos;

    public function __construct($minutos)
    {
        $this->minutos = $minutos;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Recordatorio Ludoteca')
            ->line("Faltan {$this->minutos} minutos para recoger al menor.");
    }
}