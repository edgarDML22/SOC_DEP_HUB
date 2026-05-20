<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

use App\Notifications\PreRegistroRechazadoNotification;

class NotificarRechazoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $preRegistro;

    protected $motivo;

    public $tries = 3;

    public $backoff = [60, 300, 600];

    public function __construct($preRegistro, $motivo)
    {
        $this->preRegistro = $preRegistro;
        $this->motivo = $motivo;
    }

    public function handle(): void
    {
        Log::info('Enviando correo rechazo...');

        $datos = $this->preRegistro->datos_participante;
        $correoDestino = null;

        if ($this->preRegistro->tipo === 'EQUIPO') {
            // Mandar al capitán del equipo (primer integrante)
            $correoDestino = $datos['integrantes'][0]['correo'] ?? null;
        } else {
            // Mandar al participante individual
            $correoDestino = $datos['correo'] ?? null;
        }

        if ($correoDestino) {
            Notification::route('mail', $correoDestino)
                ->notify(new PreRegistroRechazadoNotification($this->motivo));
            Log::info('Correo rechazo enviado a ' . $correoDestino);
        } else {
            Log::warning('No se pudo enviar el correo de rechazo porque no se encontró una dirección de correo.');
        }

        Log::info('Correo rechazo enviado.');
    }
}