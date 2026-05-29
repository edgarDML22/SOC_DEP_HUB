<?php

namespace App\Jobs;

use App\Models\EquiposTorneo;
use App\Models\SocioTitular;
use App\Notifications\RechazoEquipoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarRechazoEquipoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 600];

    public function __construct(
        public readonly EquiposTorneo $equipo,
        public readonly SocioTitular  $capitan,
        public readonly SocioTitular  $companero,
    ) {}

    public function handle(): void
    {
        Log::info("NotificarRechazoEquipoJob: notificando al capitán id={$this->capitan->id_socio} del rechazo en equipo id={$this->equipo->id_equipo_torneo}");

        $this->capitan->notify(new RechazoEquipoNotification(
            nombre_companero: $this->companero->nombre_completo,
            nombre_equipo:    $this->equipo->nombre_equipo ?? "Equipo #{$this->equipo->id_equipo_torneo}",
            id_equipo:        (int) $this->equipo->id_equipo_torneo,
        ));

        Log::info("NotificarRechazoEquipoJob: notificación enviada al capitán.");
    }
}
