<?php

namespace App\Jobs;

use App\Models\SocioTitular;
use App\Notifications\SancionAsignadaNotification;
use App\Notifications\SancionLevantadaNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSancionNotificationMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int     $socioId,
        public readonly string  $tipo,
        public readonly string  $nombreSocio,
        public readonly ?string $estatusPenalizacion = null,
        public readonly ?string $fechaFinReserva = null,
        public readonly ?string $fechaFinLudoteca = null,
        public readonly string  $motivo = 'manual',
    ) {}

    public function handle(): void
    {
        /** @var SocioTitular|null $socio */
        $socio = SocioTitular::find($this->socioId);
        if (!$socio || !$socio->correo_electronico) return;

        if ($this->tipo === 'asignada') {
            $notification = new SancionAsignadaMailNotification(
                estatus_penalizacion: $this->estatusPenalizacion,
                fecha_fin_reserva: $this->fechaFinReserva,
                fecha_fin_ludoteca: $this->fechaFinLudoteca,
                nombre_socio: $this->nombreSocio,
            );
        } else {
            $notification = new SancionLevantadaMailNotification(
                nombre_socio: $this->nombreSocio,
                motivo: $this->motivo,
            );
        }

        $socio->notifyNow($notification);
    }
}

// Subclases que solo envían mail — evitan duplicar el canal database
class SancionAsignadaMailNotification extends SancionAsignadaNotification
{
    public function via(object $notifiable): array { return ['mail']; }
}

class SancionLevantadaMailNotification extends SancionLevantadaNotification
{
    public function via(object $notifiable): array { return ['mail']; }
}
