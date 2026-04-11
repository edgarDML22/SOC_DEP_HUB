<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoShowPenalization extends Notification implements ShouldQueue
{
    use Queueable;

    protected $nombreEspacio;

    /**
     * Recibimos el nombre del espacio desde el CRON job
     */
    public function __construct($nombreEspacio)
    {
        $this->nombreEspacio = $nombreEspacio;
    }

    /**
     * Definimos por qué canales se enviará (Email en este caso)
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construimos la plantilla (Blade) del correo
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aviso de Inasistencia (No-Show)')
            ->greeting('Hola,')
            ->line("Hemos notado que no asististe a tu reservación en {$this->nombreEspacio}.")
            ->line('Recuerda que acumular faltas puede suspender tus privilegios de reserva.')
            ->salutation('Atentamente, la Subgerencia de SOCDEP HUB');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

}


