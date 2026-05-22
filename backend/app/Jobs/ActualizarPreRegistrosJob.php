<?php

namespace App\Jobs;

use App\Models\PreRegistroTorneo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActualizarPreRegistrosJob implements ShouldQueue
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
            "Iniciando ActualizarPreRegistrosJob para torneo {$this->idTorneo}"
        );

        PreRegistroTorneo::where('id_torneo', $this->idTorneo)
            ->where('estatus', 'PENDIENTE')
            ->update([
                'estatus' => 'TORNEO_CANCELADO'
            ]);

        Log::channel('torneo')->info(
            "Finalizado ActualizarPreRegistrosJob para torneo {$this->idTorneo}"
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            "Error en ActualizarPreRegistrosJob torneo {$this->idTorneo}: {$exception->getMessage()}"
        );
    }
}