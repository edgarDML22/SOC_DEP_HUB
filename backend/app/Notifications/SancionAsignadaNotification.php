<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SancionAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $estatus_penalizacion,
        public readonly ?string $fecha_fin_reserva,
        public readonly ?string $fecha_fin_ludoteca,
        public readonly string  $nombre_socio,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // ── Base de datos ───────────────────────────────────────────────────────
    public function toDatabase(object $notifiable): array
    {
        return [
            'tipo'                 => 'SANCION_ASIGNADA',
            'estatus_penalizacion' => $this->estatus_penalizacion,
            'fecha_fin_reserva'    => $this->fecha_fin_reserva,
            'fecha_fin_ludoteca'   => $this->fecha_fin_ludoteca,
            'nombre_socio'         => $this->nombre_socio,
        ];
    }

    // ── Correo electrónico ──────────────────────────────────────────────────
    public function toMail(object $notifiable): MailMessage
    {
        $servicios = $this->describirServicios();
        $fechas    = $this->describirFechas();

        $mail = (new MailMessage)
            ->subject('Notificación de Penalización - SOC-DEP HUB')
            ->greeting("Hola, {$this->nombre_socio}")
            ->line('La administración del club ha registrado una penalización en tu cuenta.')
            ->line("**Servicio(s) restringido(s):** {$servicios}")
            ->line($fechas);

        if ($this->fecha_fin_reserva) {
            $mail->line("**Reservaciones bloqueadas hasta:** {$this->formatearFecha($this->fecha_fin_reserva)}");
        }

        if ($this->fecha_fin_ludoteca) {
            $mail->line("**Ludoteca bloqueada hasta:** {$this->formatearFecha($this->fecha_fin_ludoteca)}");
        }

        return $mail
            ->line('Si tienes dudas, te invitamos a acudir con la gerencia del club para mayor información.')
            ->salutation('Club SOC-DEP HUB');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    private function describirServicios(): string
    {
        return match ($this->estatus_penalizacion) {
            'PENALIZADO_RESERVA'  => 'Reservaciones',
            'PENALIZADO_LUDOTECA' => 'Ludoteca',
            'PENALIZADO_AMBOS'    => 'Reservaciones y Ludoteca',
            default               => $this->estatus_penalizacion,
        };
    }

    private function describirFechas(): string
    {
        if ($this->estatus_penalizacion === 'PENALIZADO_AMBOS') {
            return 'A continuación se detallan las fechas de liberación por servicio:';
        }
        return 'A continuación se detalla la fecha en que se levantará la restricción:';
    }

    private function formatearFecha(?string $fecha): string
    {
        if (!$fecha) return 'Indefinido';
        $d = new \DateTime($fecha, new \DateTimeZone('America/Mexico_City'));
        return $d->format('d/m/Y');
    }
}
