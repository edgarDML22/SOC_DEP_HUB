<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AlertaRecogidaNotification extends Notification
{
    use Queueable;

    public $minutos;
    public $tipo;

    public function __construct($minutos = null, $tipo = 'recordatorio')
    {
        $this->minutos = $minutos;
        $this->tipo = $tipo;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // correo normal de recordatorio
        if ($this->tipo == 'recordatorio') {
            return (new MailMessage)
                ->subject('Recordatorio Ludoteca')
                ->line("Faltan {$this->minutos} minutos para recoger al menor.");
        }

        // correo de advertencia por retrasos
        if ($this->tipo == 'advertencia') {
            return (new MailMessage)
                ->subject('Advertencia por retrasos en Ludoteca')
                ->line('Has acumulado 3 retrasos al recoger al menor.')
                ->line('Si continúas acumulando retrasos, tu acceso a la ludoteca podría ser suspendido temporalmente.');
        }

        // correo de suspensión
        if ($this->tipo == 'suspension_ludoteca') {
            return (new MailMessage)
                ->subject('Suspensión temporal de Ludoteca')
                ->line('Has acumulado demasiados retrasos.')
                ->line('Tu acceso ha sido suspendido temporalmente.');
        }

        // correo de cancelación
        if ($this->tipo == 'cancelacion_ludoteca') {
            return (new MailMessage)
                ->subject('Cancelación de acceso a Ludoteca')
                ->line('Has acumulado demasiados retrasos.')
                ->line('Tu acceso a la ludoteca ha sido cancelado.');
        }
        // correo de suspensión
        if ($this->tipo == 'suspension_reservas') {
            return (new MailMessage)
                ->subject('Suspensión temporal de Reservas')
                ->line('Has acumulado demasiados retrasos.')
                ->line('Tu acceso ha sido suspendido temporalmente.');
        }

        // correo de cancelación
        if ($this->tipo == 'cancelacion_reservas') {
            return (new MailMessage)
                ->subject('Cancelación de acceso a Reservas')
                ->line('Has acumulado demasiados retrasos.')
                ->line('Tu acceso a las reservas ha sido cancelado.');
        }
    }
}