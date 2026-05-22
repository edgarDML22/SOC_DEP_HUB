<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Jobs\InvalidarQRExternosJob;
use App\Jobs\LiberarAgendaInstructoresJob;
use App\Jobs\NotificarCancelacionMasivaJob;
use App\Jobs\ActualizarPreRegistrosJob;

class CancelarTorneoAction
{
    /**
     * Cancelación completa (persistencia + efectos asíncronos).
     * Usado por rutas de prueba o flujos que no pasan por TransitionTorneoStatusAction.
     */
    public function execute(Torneo $torneo, string $motivo): void
    {
        $torneo->update([
            'estatus_torneo' => 'CANCELADO',
            'motivo_cancelacion' => $motivo,
        ]);

        $this->dispatchPostCancelacionJobs($torneo->id_torneo, $motivo);
    }

    /**
     * Efectos post-cancelación cuando el torneo ya fue marcado CANCELADO
     * (p. ej. desde TransitionTorneoStatusAction dentro de la transición de estatus).
     */
    public function ejecutarPostCancelacion(Torneo $torneo, string $motivo): void
    {
        $this->dispatchPostCancelacionJobs($torneo->id_torneo, $motivo);
    }

    protected function dispatchPostCancelacionJobs(int $idTorneo, string $motivo): void
    {
        InvalidarQRExternosJob::dispatch($idTorneo)
            ->onQueue('torneo-cancelacion');

        LiberarAgendaInstructoresJob::dispatch($idTorneo)
            ->onQueue('torneo-cancelacion');

        ActualizarPreRegistrosJob::dispatch($idTorneo)
            ->onQueue('torneo-cancelacion');

        NotificarCancelacionMasivaJob::dispatch($idTorneo, $motivo)
            ->onQueue('torneo-cancelacion');
    }
}
