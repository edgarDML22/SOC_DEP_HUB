<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\QRParticipanteNotification;

class GenerarQRParticipanteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $participante;
    protected $correoDestino;

    public $tries = 3;

    public $backoff = [60, 300, 600];

    public function __construct($participante, $correoDestino = null)
    {
        $this->participante = $participante;
        $this->correoDestino = $correoDestino;
        $this->afterCommit = true;
    }

    public function handle(): void
    {
        Log::info('Enviando correo con QR al participante...');

        // Determinar el correo a usar
        $correoFinal = $this->correoDestino ?? $this->participante->correo;

        // Enviar correo
        if ($correoFinal) {
            Notification::route('mail', $correoFinal)
                ->notify(new \App\Notifications\QRParticipanteNotification($this->participante));
        } else {
            Log::info('No se envió correo porque no hay dirección registrada (Ej. Competidor Externo).');
        }

        Log::info('Correo con QR enviado.');
    }
}