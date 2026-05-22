<?php

namespace App\Jobs;

use App\Models\ParticipantesTorneo;
use App\Models\PreRegistroTorneo;
use App\Models\Torneo;
use App\Notifications\TorneoCanceladoMail;
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

    public function __construct(int $idTorneo, string $motivo)
    {
        $this->idTorneo = $idTorneo;
        $this->motivo = $motivo;
    }

    public function handle(): void
    {
        Log::channel('torneo')->info(
            "Iniciando NotificarCancelacionMasivaJob para torneo {$this->idTorneo}"
        );

        $torneo = Torneo::findOrFail($this->idTorneo);
        $emails = [];

        ParticipantesTorneo::where('id_torneo', $this->idTorneo)
            ->with('participante')
            ->get()
            ->each(function (ParticipantesTorneo $participante) use (&$emails) {
                $correo = $this->resolverCorreoParticipante($participante);
                if ($correo) {
                    $emails[] = strtolower($correo);
                }
            });

        PreRegistroTorneo::where('id_torneo', $this->idTorneo)
            ->whereIn('estatus', ['PENDIENTE', 'APROBADO'])
            ->get()
            ->each(function (PreRegistroTorneo $preRegistro) use (&$emails) {
                $datos = $preRegistro->datos_participante ?? [];
                if (!empty($datos['correo'])) {
                    $emails[] = strtolower($datos['correo']);
                }
                foreach ($datos['integrantes'] ?? [] as $integrante) {
                    if (!empty($integrante['correo'])) {
                        $emails[] = strtolower($integrante['correo']);
                    }
                }
            });

        $emails = array_values(array_unique(array_filter($emails)));

        foreach ($emails as $email) {
            Mail::to($email)->send(new TorneoCanceladoMail($torneo, $this->motivo));
        }

        Log::channel('torneo')->info(
            "Finalizado NotificarCancelacionMasivaJob para torneo {$this->idTorneo}",
            ['correos_enviados' => count($emails)]
        );
    }

    protected function resolverCorreoParticipante(ParticipantesTorneo $participante): ?string
    {
        if ($participante->correo) {
            return $participante->correo;
        }

        $p = $participante->participante;
        if (!$p) {
            return null;
        }

        return $p->correo_electronico ?? $p->correo ?? null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('torneo')->error(
            "Error en NotificarCancelacionMasivaJob torneo {$this->idTorneo}: {$exception->getMessage()}"
        );
    }
}
