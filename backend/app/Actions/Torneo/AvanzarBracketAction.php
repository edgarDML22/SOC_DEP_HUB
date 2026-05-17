<?php

namespace App\Actions\Torneo;

use App\Models\EncuentrosTorneo;
use App\Models\Torneo;
use App\Actions\Torneo\TransitionTorneoStatusAction;
use Illuminate\Support\Facades\DB;

class AvanzarBracketAction
{
    /**
     * Propaga el ganador al siguiente encuentro del bracket tras validar el resultado.
     *
     * @param EncuentrosTorneo $encuentro
     * @return array
     */
    public function execute(EncuentrosTorneo $encuentro): array
    {
        $ganadorId = null;
        $ganadorType = null;

        // Identificar el ganador según los marcadores
        if ($encuentro->resultado_comp1 > $encuentro->resultado_comp2) {
            $ganadorId = $encuentro->competidor_1_id;
            $ganadorType = $encuentro->competidor_1_type;
        } else {
            $ganadorId = $encuentro->competidor_2_id;
            $ganadorType = $encuentro->competidor_2_type;
        }

        DB::transaction(function () use ($encuentro, $ganadorId, $ganadorType) {
            // Actualizar encuentro actual como FINALIZADO y establecer el ganador
            $encuentro->update([
                'estatus_encuentro' => 'FINALIZADO',
                'id_ganador' => $ganadorId
            ]);

            // Si es el encuentro de la FINAL del torneo
            if ($encuentro->fase_bracket === 'FINAL') {
                $torneo = Torneo::findOrFail($encuentro->id_torneo);
                app(TransitionTorneoStatusAction::class)->execute($torneo, 'FINALIZADO');
            } else {
                // Calcular el siguiente encuentro en base a la potencia de 2 del torneo
                $totalMatches = EncuentrosTorneo::where('id_torneo', $encuentro->id_torneo)->count();
                $potencia = $totalMatches + 1;

                $siguienteNumero = $this->getSiguienteEncuentroNumero($encuentro->numero_encuentro, $potencia);

                if ($siguienteNumero > 0) {
                    $siguienteEncuentro = EncuentrosTorneo::where('id_torneo', $encuentro->id_torneo)
                        ->where('numero_encuentro', $siguienteNumero)
                        ->first();

                    if ($siguienteEncuentro) {
                        // Determinar el slot (competidor_1 o competidor_2 según paridad)
                        // numero_encuentro impar → competidor_1, par → competidor_2
                        if ($encuentro->numero_encuentro % 2 !== 0) {
                            $siguienteEncuentro->update([
                                'competidor_1_id' => $ganadorId,
                                'competidor_1_type' => $ganadorType
                            ]);
                        } else {
                            $siguienteEncuentro->update([
                                'competidor_2_id' => $ganadorId,
                                'competidor_2_type' => $ganadorType
                            ]);
                        }
                    }
                }
            }
        });

        return [
            'ganador_id' => $ganadorId,
            'ganador_type' => $ganadorType
        ];
    }

    /**
     * Calcula de forma genérica el número del siguiente encuentro en el bracket.
     *
     * @param int $numeroEncuentro
     * @param int $potencia
     * @return int
     */
    private function getSiguienteEncuentroNumero(int $numeroEncuentro, int $potencia): int
    {
        $currentOffset = 0;
        $matchesInRound = $potencia / 2;

        while ($matchesInRound > 1) {
            $roundStart = $currentOffset + 1;
            $roundEnd = $currentOffset + $matchesInRound;

            if ($numeroEncuentro >= $roundStart && $numeroEncuentro <= $roundEnd) {
                $nextRoundOffset = $roundEnd;
                $relativeIndex = $numeroEncuentro - $roundStart + 1;
                return $nextRoundOffset + (int) ceil($relativeIndex / 2);
            }

            $currentOffset += $matchesInRound;
            $matchesInRound = $matchesInRound / 2;
        }

        return 0;
    }
}
