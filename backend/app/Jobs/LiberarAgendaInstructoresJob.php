<?php

namespace App\Jobs;

use App\Models\EncuentrosTorneo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LiberarAgendaInstructoresJob implements ShouldQueue
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
            "Iniciando LiberarAgendaInstructoresJob para torneo {$this->idTorneo}"
        );

        EncuentrosTorneo::where('id_torneo', $this->idTorneo)
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->update([
                'estatus_encuentro' => 'CANCELADO',
            ]);

        Log::channel('torneo')->info(
            "Finalizado LiberarAgendaInstructoresJob para torneo {$this->idTorneo}"
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            "Error en LiberarAgendaInstructoresJob torneo {$this->idTorneo}: {$exception->getMessage()}"
        );
    }
}