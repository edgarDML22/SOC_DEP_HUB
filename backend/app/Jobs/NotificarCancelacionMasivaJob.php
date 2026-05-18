<?php

namespace App\Jobs;

use App\Notifications\TorneoCanceladoMail;
use App\Models\ParticipantesTorneo;
use App\Models\Torneo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarCancelacionMasivaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 600];

    protected int $idTorneo;
    protected string $motivo;

    /**
     * Create a new job instance.
     */
    public function __construct(int $idTorneo, string $motivo)
    {
        $this->idTorneo = $idTorneo;
        $this->motivo = $motivo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('torneo')->info(
            "Iniciando NotificarCancelacionMasivaJob para torneo {$this->idTorneo}"
        );

        $torneo = Torneo::findOrFail($this->idTorneo);

        $participantes = ParticipantesTorneo::where('id_torneo', $this->idTorneo)
            ->with('participante')
            ->get();

        $emails = [];

        foreach ($participantes as $participante) {

            if (
                $participante->participante &&
                isset($participante->participante->correo_electronico)
            ) {
                $emails[] = $participante->participante->correo_electronico;
            }
        }

        $emails = array_unique($emails);

        Log::info($emails);

        Log::info('Intentando enviar correo...');

        if (!empty($emails)) {
            foreach ($emails as $email) {

                Mail::to($email)->send(
                    new TorneoCanceladoMail(
                        $torneo,
                        $this->motivo
                    )
                );
            }
        }

        Log::channel('torneo')->info(
            "Finalizado NotificarCancelacionMasivaJob para torneo {$this->idTorneo}"
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            "Error en NotificarCancelacionMasivaJob torneo {$this->idTorneo}: {$exception->getMessage()}"
        );
    }
}