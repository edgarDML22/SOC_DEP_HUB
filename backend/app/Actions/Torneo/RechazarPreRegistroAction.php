<?php

namespace App\Actions\Torneo;

use App\Jobs\NotificarRechazoJob;

class RechazarPreRegistroAction
{
    public static function execute($preRegistro, $motivo)
    {
        $preRegistro->estatus = 'RECHAZADO';
        $preRegistro->motivo_rechazo = $motivo;
        $preRegistro->save();

        NotificarRechazoJob::dispatch(
            $preRegistro,
            $motivo
        );
    }
}