<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertaNoShowNotification extends Notification
{

    public function __construct(
        public readonly int $idSesion,
        public readonly string $disciplina,
        public readonly string $fecha,
        public readonly string $horaInicio,
        public readonly int $faltasAcumuladas = 0,
        public readonly bool $estaPenalizado = false,
        public readonly ?string $fechaFinPenalizacion = null,
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
        $titulo = "Inasistencia registrada en {$this->disciplina}";

        if ($this->estaPenalizado) {
            $mensaje = "No se registró tu asistencia en la sesión de hoy a las {$horaFormateada}. Tu cuenta cuenta con una penalización activa en reservaciones.";
            $severidad = 'danger';
        } elseif ($this->faltasAcumuladas >= 2) {
            $mensaje = "No se registró tu asistencia en la sesión de hoy a las {$horaFormateada}. Cuentas con {$this->faltasAcumuladas} inasistencias acumuladas; una falta adicional generará bloqueo temporal de tus reservaciones. Recuerda cancelar con al menos 2 horas de anticipación.";
            $severidad = 'warning';
        } else {
            $mensaje = "No se registró tu asistencia en la sesión de hoy a las {$horaFormateada}. Recuerda cancelar con al menos 2 horas de anticipación para evitar sanciones en tu cuenta.";
            $severidad = 'warning';
        }

        return [
            'tipo'      => 'NO_SHOW_ALERTA',
            'titulo'    => $titulo,
            'mensaje'   => $mensaje,
            'severidad' => $severidad,
            'data'      => [
                'id_sesion'         => $this->idSesion,
                'disciplina'        => $this->disciplina,
                'fecha'             => $this->fecha,
                'hora_inicio'       => $this->horaInicio,
                'faltas_acumuladas' => $this->faltasAcumuladas,
                'esta_penalizado'   => $this->estaPenalizado,
                'fin_penalizacion'  => $this->fechaFinPenalizacion,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $horaFormateada = substr($this->horaInicio, 0, 5) . 'h';
        $nombre = $notifiable->nombre_completo ?? 'Socio';

        $mail = (new MailMessage)
            ->subject("Inasistencia Registrada ({$this->disciplina}) - Club Deportivo")
            ->greeting("Estimado(a) {$nombre},")
            ->line("Se ha registrado una inasistencia (No-Show) en la actividad **{$this->disciplina}** programada para hoy {$this->fecha} a las **{$horaFormateada}**.")
            ->line("Recuerda que para liberar el cupo y evitar penalizaciones en tu cuenta, debes cancelar tu asistencia con un mínimo de **2 horas de anticipación**.");

        if ($this->estaPenalizado) {
            $mail->line("**Aviso de sanción:** Tu cuenta se encuentra actualmente con restricciones activas para nuevas reservaciones.");
        } elseif ($this->faltasAcumuladas >= 2) {
            $mail->line("**Advertencia de penalización:** Has acumulado {$this->faltasAcumuladas} inasistencias este mes. Al acumular 3 inasistencias se suspenderá temporalmente tu privilegio de reserva.");
        }

        return $mail->salutation('Atentamente, Coordinación Deportiva');
    }
}
