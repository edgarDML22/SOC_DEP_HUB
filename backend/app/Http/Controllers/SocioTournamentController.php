<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use App\Models\MiembrosFamiliares;
use App\Models\ParticipantesTorneo;
use App\Models\EncuentrosTorneo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocioTournamentController extends Controller
{
    /**
     * Lista los torneos disponibles para inscripción, incluyendo el estatus
     * de si el socio o alguno de sus familiares ya se encuentra inscrito.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disponibles(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 419);
        }

        // Obtener el ID de socio y su modalidad de plan
        $socioId = $user->user_id;
        $socio = \App\Models\SocioTitular::find($socioId);
        $modalidadPlan = $socio ? $socio->modalidad_plan : 'INDIVIDUAL';

        // Obtener familiares elegibles si el plan es familiar
        $familyIds = [];
        if ($modalidadPlan === 'FAMILIAR') {
            $familyIds = MiembrosFamiliares::where('socio_id', $socioId)
                ->pluck('id_miembro')
                ->toArray();
        }

        // Obtener torneos en inscripción con conteo de participantes confirmados
        $torneos = Torneo::where('estatus_torneo', 'EN_INSCRIPCION')
            ->with(['disciplina', 'categoria'])
            ->withCount(['participantes as inscritos_actual' => function ($q) {
                $q->where('estatus_inscripcion', 'CONFIRMADO');
            }])
            ->get();

        // Obtener todas las inscripciones del socio o familiares para estos torneos
        $registrations = ParticipantesTorneo::whereIn('id_torneo', $torneos->pluck('id_torneo'))
            ->where(function ($q) use ($socioId, $familyIds) {
                $q->where(function ($q2) use ($socioId) {
                    $q2->where('participante_type', 'SOCIO')
                       ->where('participante_id', $socioId);
                });
                if (!empty($familyIds)) {
                    $q->orWhere(function ($q2) use ($familyIds) {
                        $q2->where('participante_type', 'FAMILIAR')
                           ->whereIn('participante_id', $familyIds);
                    });
                }
            })
            ->get()
            ->groupBy('id_torneo');

        $data = $torneos->map(function ($torneo) use ($registrations, $socioId, $familyIds, $modalidadPlan) {
            $tournamentRegs = $registrations->get($torneo->id_torneo, collect());
            $titularInscrito = $tournamentRegs->where('participante_type', 'SOCIO')->isNotEmpty();
            $familiaresInscritos = $tournamentRegs->where('participante_type', 'FAMILIAR')
                ->pluck('participante_id')
                ->toArray();

            // Lógica de ya_inscrito
            $yaInscrito = $titularInscrito;
            if ($modalidadPlan === 'FAMILIAR' && !$titularInscrito && count($familyIds) > 0) {
                $unregisteredCount = count(array_diff($familyIds, $familiaresInscritos));
                if ($unregisteredCount === 0) {
                    $yaInscrito = true;
                }
            }

            return [
                'id_torneo' => $torneo->id_torneo,
                'nombre_torneo' => $torneo->nombre_torneo,
                'fecha_inicio' => $torneo->fecha_inicio,
                'fecha_fin' => $torneo->fecha_fin,
                'disciplina' => $torneo->disciplina ? [
                    'nombre_disciplina' => $torneo->disciplina->nombre_disciplina
                ] : null,
                'categoria' => $torneo->categoria ? [
                    'nombre_categoria' => $torneo->categoria->nombre_categoria,
                    'edad_minima' => $torneo->categoria->edad_minima,
                    'edad_maxima' => $torneo->categoria->edad_maxima,
                    'genero_requerido' => $torneo->categoria->genero_requerido
                ] : null,
                'tipo_acceso' => $torneo->tipo_acceso,
                'cupo_maximo' => $torneo->cupo_maximo,
                'inscritos_actual' => (int)$torneo->inscritos_actual,
                'ya_inscrito' => $yaInscrito,
                'titular_inscrito' => $titularInscrito,
                'familiares_inscritos' => $familiaresInscritos,
                'modalidad_plan' => $modalidadPlan
            ];
        })->toArray();

        return response()->json([
            'data' => $data
        ], 200);
    }

    /**
     * Obtiene el historial de los últimos 10 torneos en los que el socio o
     * sus familiares han participado, incluyendo la fase máxima alcanzada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function historial(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.'
            ], 419);
        }

        $socioId = $user->user_id;
        $familyIds = MiembrosFamiliares::where('socio_id', $socioId)
            ->pluck('id_miembro')
            ->toArray();

        // Consultar inscripciones del socio o familiares, ordenando por fecha de inicio del torneo desc
        $participaciones = ParticipantesTorneo::where(function ($q) use ($socioId, $familyIds) {
            $q->where(function ($q2) use ($socioId) {
                $q2->where('participante_type', 'SOCIO')
                   ->where('participante_id', $socioId);
            })->orWhere(function ($q2) use ($familyIds) {
                $q2->where('participante_type', 'FAMILIAR')
                   ->whereIn('participante_id', $familyIds);
            });
        })
        ->join('torneos', 'participantes_torneo.id_torneo', '=', 'torneos.id_torneo')
        ->leftJoin('disciplinas', 'torneos.id_disciplina', '=', 'disciplinas.id_disciplina')
        ->orderBy('torneos.fecha_inicio', 'DESC')
        ->select([
            'torneos.id_torneo',
            'torneos.nombre_torneo',
            'torneos.fecha_inicio',
            'torneos.estatus_torneo',
            'disciplinas.nombre_disciplina',
            'participantes_torneo.participante_type',
            'participantes_torneo.participante_id'
        ])
        ->limit(10)
        ->get();

        $data = $participaciones->map(function ($part) {
            // Buscar los encuentros finalizados de este participante en este torneo
            $matches = EncuentrosTorneo::where('id_torneo', $part->id_torneo)
                ->where('estatus_encuentro', 'FINALIZADO')
                ->where(function ($q) use ($part) {
                    $q->where(function ($q2) use ($part) {
                        $q2->where('competidor_1_type', $part->participante_type)
                           ->where('competidor_1_id', $part->participante_id);
                    })->orWhere(function ($q2) use ($part) {
                        $q2->where('competidor_2_type', $part->participante_type)
                           ->where('competidor_2_id', $part->participante_id);
                    });
                })
                ->get();

            $faseMaxima = $this->getMaxPhase($matches);

            return [
                'id_torneo' => $part->id_torneo,
                'nombre_torneo' => $part->nombre_torneo,
                'fecha_inicio' => $part->fecha_inicio,
                'disciplina' => $part->nombre_disciplina,
                'estatus_torneo' => $part->estatus_torneo,
                'fase_maxima_alcanzada' => $faseMaxima
            ];
        })->toArray();

        return response()->json([
            'data' => $data
        ], 200);
    }

    /**
     * Calcula la fase de bracket máxima alcanzada a partir de una lista de encuentros.
     *
     * @param  \Illuminate\Support\Collection  $matches
     * @return string|null
     */
    private function getMaxPhase($matches)
    {
        if ($matches->isEmpty()) {
            return null;
        }

        $phasePriority = [
            '16VOS' => 1,
            '8VOS' => 2,
            'OCTAVOS' => 2,
            'CUARTOS' => 3,
            'SEMIFINAL' => 4,
            'SEMIFINALES' => 4,
            'FINAL' => 5,
        ];

        $maxPhase = null;
        $maxPriority = -1;

        foreach ($matches as $match) {
            $phase = $match->fase_bracket;
            if (!$phase) {
                continue;
            }

            $upperPhase = strtoupper(trim($phase));
            $priority = $phasePriority[$upperPhase] ?? 0;

            if ($priority > $maxPriority) {
                $maxPriority = $priority;
                $maxPhase = $phase; // Devuelve el valor original guardado en la base de datos
            }
        }

        return $maxPhase;
    }
}
