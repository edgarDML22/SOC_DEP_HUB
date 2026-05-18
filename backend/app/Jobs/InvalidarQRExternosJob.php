<?php

namespace App\Jobs;

use App\Models\ParticipantesTorneo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvalidarQRExternosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 600];

    protected int $idTorneo;

    /**
     * Create a new job instance.
     */
    public function __construct(int $idTorneo)
    {
        $this->idTorneo = $idTorneo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('torneo')->info(
            "Iniciando InvalidarQRExternosJob para torneo {$this->idTorneo}"
        );

        ParticipantesTorneo::where('id_torneo', $this->idTorneo)
            ->whereNotNull('qr_codigo')
            ->update([
                'qr_estatus' => 'INACTIVO'
            ]);

        Log::channel('torneo')->info(
            "Finalizado InvalidarQRExternosJob para torneo {$this->idTorneo}"
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            "Error en InvalidarQRExternosJob torneo {$this->idTorneo}: {$exception->getMessage()}"
        );
    }
}