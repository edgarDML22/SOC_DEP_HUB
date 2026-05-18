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
     * Ejecuta la cancelación orquestada del torneo.
     *
     * @param Torneo $torneo
     * @param string $motivo
     * @return void
     */
    public function execute(Torneo $torneo, string $motivo): void
    {
        // Parte síncrona
        $torneo->update([
            'estatus_torneo' => 'CANCELADO',
            'motivo_cancelacion' => $motivo,
        ]);

        // Jobs asíncronos
        InvalidarQRExternosJob::dispatch($torneo->id_torneo)
            ->onQueue('torneo-cancelacion');

        LiberarAgendaInstructoresJob::dispatch($torneo->id_torneo)
            ->onQueue('torneo-cancelacion');

        ActualizarPreRegistrosJob::dispatch($torneo->id_torneo)
            ->onQueue('torneo-cancelacion');

        NotificarCancelacionMasivaJob::dispatch(
            $torneo->id_torneo,
            $motivo
        )->onQueue('torneo-cancelacion');
    }
}