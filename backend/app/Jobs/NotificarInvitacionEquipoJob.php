<?php

namespace App\Jobs;

use App\Models\EquiposTorneo;
use App\Models\SocioTitular;
use App\Notifications\InvitacionEquipoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificarInvitacionEquipoJob implements ShouldQueue
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
        Log::info("NotificarInvitacionEquipoJob: enviando invitación al compañero id={$this->companero->id_socio} para equipo id={$this->equipo->id_equipo_torneo}");

        $this->companero->notify(new InvitacionEquipoNotification(
            nombre_capitan: $this->capitan->nombre_completo,
            id_capitan:     (int) $this->capitan->id_socio,
            nombre_equipo:  $this->equipo->nombre_equipo ?? "Equipo #{$this->equipo->id_equipo_torneo}",
            id_equipo:      (int) $this->equipo->id_equipo_torneo,
        ));

        Log::info("NotificarInvitacionEquipoJob: notificación enviada.");
    }
}
