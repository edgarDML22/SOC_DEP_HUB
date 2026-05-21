<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Actions\Torneo\GenerarBracketAction;
use App\Actions\Torneo\AsignarHorariosAction;
use App\Actions\Torneo\BloquearClasesTorneoAction;
use App\Actions\Torneo\DesbloquearClasesTorneoAction;

class TransitionTorneoStatusAction
{
    const TRANSICIONES = [

        'EN_PLANIFICACION' => [
            'EN_INSCRIPCION',
            'CANCELADO'
        ],

        'EN_INSCRIPCION' => [
            'PROGRAMADO',
            'CANCELADO'
        ],

        'PROGRAMADO' => [
            'EN_CURSO',
            'CANCELADO'
        ],

        'EN_CURSO' => [
            'FINALIZADO',
            'CANCELADO'
        ],
    ];

    public function execute(
        Torneo $torneo,
        string $nuevoEstatus,
        ?string $motivoCancelacion = null

    ) {

        $estatusActual = $torneo->estatus_torneo;

        $permitidos = self::TRANSICIONES[$estatusActual] ?? [];

        if (!in_array($nuevoEstatus, $permitidos)) {

            throw ValidationException::withMessages([
                'message' => "Transición inválida: {$estatusActual} no puede pasar a {$nuevoEstatus}"
            ]);
        }

        if (
            $nuevoEstatus === 'CANCELADO'
            && empty($motivoCancelacion)
        ) {

            throw ValidationException::withMessages([
                'message' => 'El motivo de cancelación es obligatorio.'
            ]);
        }

        DB::transaction(function () use ($torneo, $nuevoEstatus, $motivoCancelacion) {

            $torneo->estatus_torneo = $nuevoEstatus;

            if ($nuevoEstatus === 'CANCELADO') {

                $torneo->motivo_cancelacion = $motivoCancelacion;

                // Task-23
                // app(CancelarTorneoAction::class)->execute($torneo);
            }

            if ($nuevoEstatus === 'PROGRAMADO') {
                app(GenerarBracketAction::class)->execute($torneo);
                app(AsignarHorariosAction::class)->execute($torneo);
                app(BloquearClasesTorneoAction::class)->execute($torneo);
            }

            if (in_array($nuevoEstatus, ['FINALIZADO', 'CANCELADO'])) {
                app(DesbloquearClasesTorneoAction::class)->execute($torneo);
            }

            $torneo->save();
        });

        return [
            'success' => true,
            'id_torneo' => $torneo->id_torneo,
            'estatus_torneo' => $torneo->estatus_torneo
        ];
    }
}