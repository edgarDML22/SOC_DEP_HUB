<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SesionCanceladaClubNotification extends Notification
{

    public function __construct(
        public readonly int $idSesion,
        public readonly string $disciplina,
        public readonly string $fecha,
        public readonly string $horaInicio,
        public readonly ?string $motivo = 'condiciones climáticas o causas de fuerza mayor',
    ) {}

    public function via(object $notifiable): array
    {
        $canales = ['database'];

        if (!empty($notifiable->correo_electronico)) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    public function toDatabase(object $notifiable): array
    {
        $horaFormateada = substr($this->horaInicio, 0, 5) . 'h';

        return [
            'tipo'      => 'SESION_CANCELADA_CLUB',
            'titulo'    => "Cancelación de sesión: {$this->disciplina}",
            'mensaje'   => "La sesión de {$this->disciplina} programada para el {$this->fecha} a las {$horaFormateada} ha sido cancelada por el club debido a {$this->motivo}. Esta cancelación no afecta tu historial de asistencia.",
            'severidad' => 'info',
            'data'      => [
                'id_sesion'   => $this->idSesion,
                'disciplina'  => $this->disciplina,
                'fecha'       => $this->fecha,
                'hora_inicio' => $this->horaInicio,
                'motivo'      => $this->motivo,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $horaFormateada = substr($this->horaInicio, 0, 5) . 'h';
        $nombre = $notifiable->nombre_completo ?? 'Socio';

        return (new MailMessage)
            ->subject("Aviso de Cancelación de Sesión ({$this->disciplina}) - Club Deportivo")
            ->greeting("Estimado(a) {$nombre},")
            ->line("Te informamos que la sesión de **{$this->disciplina}** del día {$this->fecha} a las **{$horaFormateada}** ha sido cancelada por la administración del club debido a **{$this->motivo}**.")
            ->line("Esta cancelación fue realizada por causas de fuerza mayor y **no computa** como inasistencia en tu registro personal.")
            ->salutation('Atentamente, Coordinación Deportiva');
    }
}
