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
     * Flujo automático de estatus del torneo:
     *   - Si el torneo está PROGRAMADO al validar el primer resultado → pasa a EN_CURSO
     *   - Si el encuentro es la FINAL → el torneo pasa a FINALIZADO
     *
     * @param EncuentrosTorneo $encuentro
     * @return array
     */
    public function execute(EncuentrosTorneo $encuentro): array
    {
        // Determinar el ganador según los marcadores
        if ($encuentro->resultado_comp1 > $encuentro->resultado_comp2) {
            $ganadorId   = $encuentro->competidor_1_id;
        } elseif ($encuentro->resultado_comp2 > $encuentro->resultado_comp1) {
            $ganadorId   = $encuentro->competidor_2_id;
        } else {
            // Empate — no debería llegar aquí (el controller lo bloquea), pero se defiende
            $ganadorId   = $encuentro->competidor_1_id;
        }

        // El type morph usa el alias normalizado del MorphMap (no la clase completa)
        $ganadorType = 'PARTICIPANTE';

        $torneoFinalizado = false;

        DB::transaction(function () use ($encuentro, $ganadorId, $ganadorType, &$torneoFinalizado) {

            // Marcar encuentro actual como FINALIZADO con el ganador
            $encuentro->update([
                'estatus_encuentro' => 'FINALIZADO',
                'id_ganador'        => $ganadorId
            ]);

            // Cargar torneo una sola vez para gestionar transiciones de estatus
            $torneo = Torneo::findOrFail($encuentro->id_torneo);

            // Transición automática PROGRAMADO → EN_CURSO al validar el primer resultado
            if ($torneo->estatus_torneo === 'PROGRAMADO') {
                app(TransitionTorneoStatusAction::class)->execute($torneo, 'EN_CURSO');
                // Recargar para tener el estatus actualizado
                $torneo->refresh();
            }

            // Si es el encuentro de la FINAL → transicionar torneo a FINALIZADO
            if ($encuentro->fase_bracket === 'FINAL') {
                app(TransitionTorneoStatusAction::class)->execute($torneo, 'FINALIZADO');
                $torneoFinalizado = true;
                return;
            }

            // ── Calcular el siguiente encuentro ──────────────────────────────────────────
            //
            // En un bracket de eliminación directa con `p` participantes-potencia:
            //   · totalMatches = p - 1  →  p (potencia) = totalMatches + 1
            // Todos los slots (incluidos los vacíos de rondas posteriores) se cuentan,
            // por lo que esta fórmula es válida tanto con Byes como sin ellos.

            $totalMatches = EncuentrosTorneo::where('id_torneo', $encuentro->id_torneo)->count();
            $potencia     = $totalMatches + 1;

            $siguienteNumero = $this->getSiguienteEncuentroNumero(
                (int) $encuentro->numero_encuentro,
                (int) $potencia
            );

            if ($siguienteNumero > 0) {
                $siguienteEncuentro = EncuentrosTorneo::where('id_torneo', $encuentro->id_torneo)
                    ->where('numero_encuentro', $siguienteNumero)
                    ->first();

                if ($siguienteEncuentro) {
                    // Posición del ganador en el siguiente encuentro:
                    // número impar dentro de su ronda → competidor_1
                    // número par  dentro de su ronda  → competidor_2
                    if ((int) $encuentro->numero_encuentro % 2 !== 0) {
                        $siguienteEncuentro->update([
                            'competidor_1_id'   => $ganadorId,
                            'competidor_1_type' => $ganadorType,
                        ]);
                    } else {
                        $siguienteEncuentro->update([
                            'competidor_2_id'   => $ganadorId,
                            'competidor_2_type' => $ganadorType,
                        ]);
                    }
                }
            }
        });

        return [
            'ganador_id'        => $ganadorId,
            'ganador_type'      => $ganadorType,
            'torneo_finalizado' => $torneoFinalizado,
        ];
    }

    /**
     * Calcula de forma genérica el número del siguiente encuentro en el bracket.
     *
     * Los encuentros se numeran consecutivamente ronda por ronda:
     *   Ronda 1: 1 .. p/2
     *   Ronda 2: p/2+1 .. p/2+p/4
     *   …
     *   Final:   p-1
     *
     * @param int $numeroEncuentro  Número del encuentro actual (1-indexed)
     * @param int $potencia         Potencia del bracket (4, 8, 16, 32…)
     * @return int  Número del siguiente encuentro, o 0 si no existe (es la Final)
     */
    private function getSiguienteEncuentroNumero(int $numeroEncuentro, int $potencia): int
    {
        $currentOffset  = 0;
        $matchesInRound = $potencia / 2;

        while ($matchesInRound > 1) {
            $roundStart = $currentOffset + 1;
            $roundEnd   = $currentOffset + $matchesInRound;

            if ($numeroEncuentro >= $roundStart && $numeroEncuentro <= $roundEnd) {
                $nextRoundOffset = $roundEnd;
                $relativeIndex   = $numeroEncuentro - $roundStart + 1;
                return $nextRoundOffset + (int) ceil($relativeIndex / 2);
            }

            $currentOffset  += $matchesInRound;
            $matchesInRound  = $matchesInRound / 2;
        }

        return 0; // Es la Final — no hay siguiente
    }
}
