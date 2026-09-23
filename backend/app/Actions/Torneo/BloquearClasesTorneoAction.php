<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Models\EncuentrosTorneo;
use App\Models\SesionActiva;
use App\Models\ActividadPlantilla;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BloquearClasesTorneoAction
{
    /**
     * Cancela las sesiones activas que colisionen con los encuentros
     * programados del torneo, marcándolas como 'CANCELADA_POR_TORNEO'.
     *
     * La lógica identifica las actividades_plantilla que:
     *   1. Usen los mismos espacios asignados a los encuentros del torneo
     *   2. Caigan en los días de la semana que cubre el rango del torneo
     *   3. Tengan horarios que solapen con algún encuentro programado
     *
     * @param Torneo $torneo
     */
    public function execute(Torneo $torneo): void
    {
        // 1. Obtener los id_espacio distintos asignados a encuentros del torneo
        $espacioIds = EncuentrosTorneo::where('id_torneo', $torneo->id_torneo)
            ->whereNotNull('id_espacio')
            ->distinct()
            ->pluck('id_espacio')
            ->toArray();

        if (empty($espacioIds)) {
            return; // No hay espacios asignados, nada que bloquear
        }

        // 2. Mapear los días de la semana cubiertos por el rango del torneo
        $diasSemana = $this->obtenerDiasSemanaEnRango(
            $torneo->fecha_inicio,
            $torneo->fecha_fin
        );

        // 3. Obtener los encuentros con horarios para verificar solapamiento
        $encuentros = EncuentrosTorneo::where('id_torneo', $torneo->id_torneo)
            ->whereNotNull('fecha_hora_inicio')
            ->whereNotNull('fecha_hora_fin')
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'BYE'])
            ->get();

        if ($encuentros->isEmpty()) {
            return;
        }

        // 4. Obtener las actividades_plantilla en conflicto
        $actividadesConflicto = ActividadPlantilla::whereIn('id_espacio', $espacioIds)
            ->whereIn('dia_semana', $diasSemana)
            ->where('estatus', 'ACTIVO')
            ->get();

        // 5. Para cada actividad, verificar si sus horarios solapan con algún encuentro
        $actividadIdsABloquear = [];

        foreach ($actividadesConflicto as $actividad) {
            foreach ($encuentros as $encuentro) {
                // Solo comparar si el espacio coincide
                if ($actividad->id_espacio != $encuentro->id_espacio) {
                    continue;
                }

                // Solo comparar si el día de la semana del encuentro coincide con la actividad
                $diaEncuentro = $this->carbonDayToDiaSemana(
                    Carbon::parse($encuentro->fecha_hora_inicio)->dayOfWeek
                );

                if ($actividad->dia_semana !== $diaEncuentro) {
                    continue;
                }

                // Verificar solapamiento de horarios
                $encInicio = Carbon::parse($encuentro->fecha_hora_inicio)->format('H:i');
                $encFin    = Carbon::parse($encuentro->fecha_hora_fin)->format('H:i');
                $actInicio = substr($actividad->hora_inicio, 0, 5);
                $actFin    = substr($actividad->hora_fin, 0, 5);

                if ($actInicio < $encFin && $actFin > $encInicio) {
                    $actividadIdsABloquear[] = $actividad->id_actividad_plantilla;
                    break; // Ya se confirmó conflicto, no seguir revisando
                }
            }
        }

        if (empty($actividadIdsABloquear)) {
            return;
        }

        // 6. Cancelar las sesiones_activas futuras vinculadas a esas actividades
        //    dentro del rango del torneo
        SesionActiva::withoutGlobalScopes()
            ->whereIn('id_actividad_plantilla', $actividadIdsABloquear)
            ->whereBetween('fecha_sesion', [$torneo->fecha_inicio, $torneo->fecha_fin])
            ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA', 'CANCELADA_POR_TORNEO'])
            ->update(['estatus_sesion' => 'CANCELADA_POR_TORNEO']);
    }

    /**
     * Obtiene los días de la semana (enum_dia_semana) que cubre un rango de fechas.
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array ['LUNES', 'MARTES', ...]
     */
    private function obtenerDiasSemanaEnRango(string $fechaInicio, string $fechaFin): array
    {
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $dias = [];

        foreach ($periodo as $fecha) {
            $dia = $this->carbonDayToDiaSemana($fecha->dayOfWeek);
            if (!in_array($dia, $dias)) {
                $dias[] = $dia;
            }
        }

        return $dias;
    }

    /**
     * Convierte el dayOfWeek de Carbon (0=Domingo, 6=Sábado) al enum_dia_semana de PostgreSQL.
     *
     * @param int $dayOfWeek
     * @return string
     */
    private function carbonDayToDiaSemana(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'DOMINGO',
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
        };
    }
}
