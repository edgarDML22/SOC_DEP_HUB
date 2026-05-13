<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SancionLevantadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $nombre_socio,
        public readonly string  $motivo = 'manual', // 'manual' | 'automatico'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'         => 'SANCION_LEVANTADA',
            'nombre_socio' => $this->nombre_socio,
            'motivo'       => $this->motivo,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $motivoTexto = $this->motivo === 'automatico'
            ? 'El período de penalización ha concluido de forma automática.'
            : 'Un administrador del club ha levantado la restricción en tu cuenta.';

        return (new MailMessage)
            ->subject('Penalización Levantada - SOC-DEP HUB')
            ->greeting("Hola, {$this->nombre_socio}")
            ->line('Nos complace informarte que la penalización en tu cuenta ha sido **levantada**.')
            ->line($motivoTexto)
            ->line('Todos tus servicios están nuevamente disponibles. ¡Bienvenido de vuelta!')
            ->salutation('Club SOC-DEP HUB');
    }
}
