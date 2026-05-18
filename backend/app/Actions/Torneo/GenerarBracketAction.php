<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Models\ParticipantesTorneo;
use App\Models\EncuentrosTorneo;
use App\Exceptions\InsufficientParticipantsException;
use Illuminate\Support\Facades\DB;

class GenerarBracketAction
{
    /**
     * Orquesta la generación completa del bracket e inserta todos los encuentros
     * de la primera ronda y rondas vacías subsecuentes en una transacción atómica.
     *
     * @param Torneo $torneo
     * @throws InsufficientParticipantsException
     */
    public function execute(Torneo $torneo): void
    {

        // 1. Obtener participantes inscritos y confirmados ordenados por ranking_declarado DESC
        $participantes = ParticipantesTorneo::where('id_torneo', $torneo->id_torneo)
            ->where('estatus_participacion', 'ACTIVO')
            ->orderBy('ranking_declarado', 'desc')
            ->get();

        $n = count($participantes);

        // 2. Validar contra el cupo mínimo
        if ($n < $torneo->cupo_minimo) {
            throw new InsufficientParticipantsException();
        }

        // 3. Calcular la potencia de 2 superior más cercana y la cantidad de Byes
        $potencia = (int) pow(2, ceil(log($n, 2)));
        $byes = $potencia - $n;

        // DB Transaction atómica
        DB::transaction(function () use ($torneo, $participantes, $n, $potencia, $byes) {

            // 4. Actualizar participantes_torneo.id_interno con el número de seed asignado (1..n)
            foreach ($participantes as $index => $participante) {
                $seed = $index + 1;
                $participante->update([
                    'id_interno' => $seed
                ]);
            }

            // Mapear participantes por su seed para búsqueda rápida (1-indexed)
            $seedsMap = [];
            foreach ($participantes as $index => $participante) {
                $seedsMap[$index + 1] = $participante;
            }

            // 5. Determinar fase_bracket inicial
            // 32→16VOS, 16→8VOS, 8→CUARTOS, 4→SEMIFINALES, 2→FINAL
            $matchesInRound = $potencia / 2;
            $currentFase = $this->getFaseName($matchesInRound);

            $numeroEncuentro = 1;

            // Generar encuentros de primera ronda aplicando la Regla de Sándwich
            for ($i = 1; $i <= $matchesInRound; $i++) {
                $seed1 = $i;
                $seed2 = $potencia - $i + 1;

                $comp1 = $seedsMap[$seed1] ?? null;
                $comp2 = $seedsMap[$seed2] ?? null;

                if ($comp2 === null) {
                    // Es un Bye: Solo competidor_1_id (seed favorecido), es_bye = true, estatus_encuentro = BYE, id_ganador = competidor_1_id
                    EncuentrosTorneo::create([
                        'id_torneo' => $torneo->id_torneo,
                        'fase_bracket' => $currentFase,
                        'competidor_1_id' => $comp1->id_participante_torneo,
                        'competidor_1_type' => $comp1->participante_type,
                        'competidor_2_id' => null,
                        'competidor_2_type' => null,
                        'es_bye' => true,
                        'estatus_encuentro' => 'BYE',
                        'id_ganador' => $comp1->id_participante_torneo,
                        'numero_encuentro' => $numeroEncuentro++
                    ]);
                } else {
                    // Encuentro normal de primera ronda
                    EncuentrosTorneo::create([
                        'id_torneo' => $torneo->id_torneo,
                        'fase_bracket' => $currentFase,
                        'competidor_1_id' => $comp1->id_participante_torneo,
                        'competidor_1_type' => $comp1->participante_type,
                        'competidor_2_id' => $comp2->id_participante_torneo,
                        'competidor_2_type' => $comp2->participante_type,
                        'es_bye' => false,
                        'estatus_encuentro' => 'PENDIENTE',
                        'numero_encuentro' => $numeroEncuentro++
                    ]);
                }
            }

            // 6. Crear encuentros vacíos para fases subsecuentes
            $matchesInRound = $matchesInRound / 2;
            while ($matchesInRound >= 1) {
                $fase = $this->getFaseName($matchesInRound);
                for ($i = 1; $i <= $matchesInRound; $i++) {
                    EncuentrosTorneo::create([
                        'id_torneo' => $torneo->id_torneo,
                        'fase_bracket' => $fase,
                        'competidor_1_id' => null,
                        'competidor_1_type' => null,
                        'competidor_2_id' => null,
                        'competidor_2_type' => null,
                        'es_bye' => false,
                        'estatus_encuentro' => 'PENDIENTE',
                        'numero_encuentro' => $numeroEncuentro++
                    ]);
                }
                $matchesInRound = $matchesInRound / 2;
            }
        });
    }

    /**
     * Retorna el nombre de la fase en base a la cantidad de encuentros de la ronda.
     *
     * @param int $matchesCount
     * @return string
     */
    private function getFaseName(int $matchesCount): string
    {
        return match ($matchesCount) {
            16 => '16VOS',
            8 => '8VOS',
            4 => 'CUARTOS',
            2 => 'SEMIFINALES',
            1 => 'FINAL',
            default => 'FINAL'
        };
    }
}
