<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Models\Disciplina;
use App\Models\EncuentrosTorneo;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Exceptions\InsufficientSlotsException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AsignarHorariosAction
{
    /**
     * Duración estándar de cada encuentro en minutos.
     */
    const DURACION_ENCUENTRO_MINUTOS = 60;

    /**
     * Ventana operativa del club (hora inicio / hora fin).
     */
    const HORA_APERTURA = '07:00';
    const HORA_CIERRE   = '23:00';

    const FASES_ORDEN = [
        '64VOS', '32VOS', '16VOS', '8VOS', 'CUARTOS', 'SEMIFINALES', 'FINAL'
    ];

    /**
     * Asigna fecha_hora_inicio, fecha_hora_fin e id_espacio a cada
     * encuentro no-BYE del bracket, respetando la disponibilidad de
     * reservaciones, clases y otros encuentros de torneo.
     *
     * @param Torneo $torneo
     * @throws InsufficientSlotsException
     */
    public function execute(Torneo $torneo): void
    {
        // 1. Obtener espacios compatibles con la disciplina del torneo
        $espacios = Disciplina::find($torneo->id_disciplina)
            ->espacios()
            ->where('estatus', 'ACTIVO')
            ->get();

        if ($espacios->isEmpty()) {
            throw new InsufficientSlotsException(
                'No hay espacios físicos activos asociados a la disciplina del torneo.'
            );
        }

        $espacioIds = $espacios->pluck('id_espacio')->toArray();

        // 2. Obtener todos los encuentros del torneo que requieren horario
        $todosEncuentros = EncuentrosTorneo::where('id_torneo', $torneo->id_torneo)
            ->where(function ($q) {
                $q->where('es_bye', false)->orWhereNull('es_bye');
            })
            ->whereNull('fecha_hora_inicio')
            ->orderBy('numero_encuentro', 'asc')
            ->get();

        if ($todosEncuentros->isEmpty()) {
            return; // nada que programar
        }

        // 3. Agrupar encuentros por fase respetando el orden lógico
        $encuentrosPorFase = [];
        foreach (self::FASES_ORDEN as $fase) {
            $encs = $todosEncuentros->where('fase_bracket', $fase)->values();
            if ($encs->isNotEmpty()) {
                $encuentrosPorFase[$fase] = $encs;
            }
        }
        $fases = array_keys($encuentrosPorFase);

        // 4. Distribuir los días disponibles entre las fases
        $fechaInicio = Carbon::parse($torneo->fecha_inicio);
        $fechaFin    = Carbon::parse($torneo->fecha_fin);
        $totalDias   = $fechaInicio->diffInDays($fechaFin) + 1;
        $diasPorFase = $this->distribuirDias($fases, $totalDias, $fechaInicio, $fechaFin);

        // 5. Para cada fase, generar slots sólo en sus días asignados y asignarlos
        foreach ($fases as $fase) {
            $encuentrosFase = $encuentrosPorFase[$fase];
            [$diaDesde, $diaHasta] = $diasPorFase[$fase];

            $slotsLibres = $this->generarSlotsLibres(
                $diaDesde->toDateString(),
                $diaHasta->toDateString(),
                $espacioIds,
                self::DURACION_ENCUENTRO_MINUTOS
            );

            if (count($slotsLibres) < $encuentrosFase->count()) {
                throw new InsufficientSlotsException(
                    "No hay suficientes slots para la fase {$fase}: se necesitan "
                    . $encuentrosFase->count() . ' pero solo hay ' . count($slotsLibres)
                );
            }

            foreach ($encuentrosFase as $index => $encuentro) {
                $slot = $slotsLibres[$index];
                $encuentro->update([
                    'id_espacio'        => $slot['id_espacio'],
                    'fecha_hora_inicio' => $slot['inicio'],
                    'fecha_hora_fin'    => $slot['fin'],
                ]);
            }
        }
    }

    /**
     * Distribuye el rango de fechas disponibles (fechaInicio a fechaFin) entre las fases.
     * La final siempre ocupa el último día disponible (o los últimos si hay muy pocas fases).
     * Las demás fases se reparten los días anteriores de forma equitativa.
     */
    private function distribuirDias(array $fases, int $totalDias, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $numFases = count($fases);
        $diasParaFinal = 1;

        if ($numFases === 1) {
            return [$fases[0] => [$fechaInicio->copy(), $fechaFin->copy()]];
        }

        $fasesAntes = array_slice($fases, 0, $numFases - 1);
        $faseUltima = $fases[$numFases - 1];

        $diasDisponibles = max($numFases - 1, $totalDias - $diasParaFinal);
        $diasBase  = (int) floor($diasDisponibles / count($fasesAntes));
        $diasExtra = $diasDisponibles % count($fasesAntes);

        $resultado = [];
        $cursor = $fechaInicio->copy();

        foreach ($fasesAntes as $i => $fase) {
            $dias = $diasBase + ($i < $diasExtra ? 1 : 0);
            $dias = max(1, $dias);

            $desde  = $cursor->copy();
            $hasta  = $cursor->copy()->addDays($dias - 1);

            $limiteMax = $fechaFin->copy()->subDay();
            if ($hasta->gt($limiteMax)) {
                $hasta = $limiteMax->copy();
            }

            $resultado[$fase] = [$desde, $hasta];
            $cursor = $hasta->copy()->addDay();
        }

        $resultado[$faseUltima] = [$fechaFin->copy(), $fechaFin->copy()];

        return $resultado;
    }

    /**
     * Genera un array ordenado de slots libres para todos los espacios
     * en el rango de fechas del torneo, distribuyendo en round-robin
     * por espacio para paralelizar.
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param array  $espacioIds
     * @param int    $duracionMin
     * @return array  [['id_espacio'=>int, 'inicio'=>Carbon, 'fin'=>Carbon], ...]
     */
    private function generarSlotsLibres(
        string $fechaInicio,
        string $fechaFin,
        array  $espacioIds,
        int    $duracionMin
    ): array {
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        // Pre-cargar todos los bloques ocupados del rango para evitar queries N+1
        $ocupadosPrecargados = $this->preCargarOcupados($espacioIds, $fechaInicio, $fechaFin);

        // Agrupar slots por espacio para hacer round-robin después
        $slotsPorEspacio = [];
        foreach ($espacioIds as $idEspacio) {
            $slotsPorEspacio[$idEspacio] = [];
        }

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->toDateString();

            foreach ($espacioIds as $idEspacio) {
                $ocupados = $this->obtenerBloquesOcupados($ocupadosPrecargados, $idEspacio, $fechaStr);
                $libres   = $this->calcularSlotsLibres($fechaStr, $ocupados, $duracionMin);

                foreach ($libres as $slot) {
                    $slotsPorEspacio[$idEspacio][] = [
                        'id_espacio' => $idEspacio,
                        'inicio'     => $slot['inicio'],
                        'fin'        => $slot['fin'],
                    ];
                }
            }
        }

        // Round-robin: intercalar slots de cada espacio para distribuir carga
        return $this->intercalarSlots($slotsPorEspacio);
    }

    /**
     * Obtiene todos los bloques de tiempo ocupados para un espacio en una fecha.
     * Consulta: reservaciones, sesiones de clases y otros encuentros de torneo.
     *
     * @param int    $idEspacio
     * @param string $fecha
     * @return array [['inicio' => 'HH:MM', 'fin' => 'HH:MM'], ...]
     */
    private function preCargarOcupados(array $espacioIds, string $fechaInicio, string $fechaFin): array
    {
        $ocupados = [];
        $ahora = Carbon::now('America/Mexico_City');

        // A. Reservaciones activas
        $reservaciones = Reservacion::whereIn('id_espacio', $espacioIds)
            ->whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->where(function ($q) use ($ahora) {
                $q->where('estatus_operativo', 'ACTIVA')
                  ->orWhere(function ($sub) use ($ahora) {
                      $sub->where('estatus_operativo', 'PENDIENTE')
                          ->where('fecha_expiracion', '>', $ahora);
                  });
            })
            ->get(['id_espacio', 'fecha_reserva', 'hora_inicio', 'hora_fin']);

        foreach ($reservaciones as $r) {
            $fecha = $r->fecha_reserva;
            $ocupados[$r->id_espacio][$fecha][] = [
                'inicio' => substr($r->hora_inicio, 0, 5),
                'fin'    => substr($r->hora_fin, 0, 5),
            ];
        }

        // B. Sesiones activas (clases)
        $sesiones = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [$fechaInicio, $fechaFin])
            ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA', 'CANCELADA_POR_TORNEO'])
            ->whereHas('actividadPlantilla', function ($q) use ($espacioIds) {
                $q->whereIn('id_espacio', $espacioIds);
            })
            ->with('actividadPlantilla:id_actividad_plantilla,id_espacio,hora_inicio,hora_fin')
            ->get();

        foreach ($sesiones as $s) {
            $ap = $s->actividadPlantilla;
            if ($ap) {
                $fecha = $s->fecha_sesion;
                $ocupados[$ap->id_espacio][$fecha][] = [
                    'inicio' => substr($ap->hora_inicio, 0, 5),
                    'fin'    => substr($ap->hora_fin, 0, 5),
                ];
            }
        }

        // C. Otros encuentros ya programados
        $encuentros = EncuentrosTorneo::whereIn('id_espacio', $espacioIds)
            ->whereBetween(DB::raw('DATE(fecha_hora_inicio)'), [$fechaInicio, $fechaFin])
            ->whereNotNull('fecha_hora_inicio')
            ->whereNotNull('fecha_hora_fin')
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->get(['id_espacio', 'fecha_hora_inicio', 'fecha_hora_fin']);

        foreach ($encuentros as $enc) {
            $fecha = Carbon::parse($enc->fecha_hora_inicio)->toDateString();
            $ocupados[$enc->id_espacio][$fecha][] = [
                'inicio' => Carbon::parse($enc->fecha_hora_inicio)->format('H:i'),
                'fin'    => Carbon::parse($enc->fecha_hora_fin)->format('H:i'),
            ];
        }

        return $ocupados;
    }

    /**
     * Obtiene los bloques ocupados pre-cargados para un espacio y fecha.
     */
    private function obtenerBloquesOcupados(array $precargados, int $idEspacio, string $fecha): array
    {
        $ocupados = $precargados[$idEspacio][$fecha] ?? [];

        usort($ocupados, fn($a, $b) => strcmp($a['inicio'], $b['inicio']));

        return $ocupados;
    }

    /**
     * Dado un array de bloques ocupados, calcula los slots libres
     * en la ventana operativa particionados por duración.
     *
     * @param string $fecha
     * @param array  $ocupados
     * @param int    $duracionMin
     * @return array [['inicio' => Carbon, 'fin' => Carbon], ...]
     */
    private function calcularSlotsLibres(string $fecha, array $ocupados, int $duracionMin): array
    {
        $apertura = Carbon::parse($fecha . ' ' . self::HORA_APERTURA);
        $cierre   = Carbon::parse($fecha . ' ' . self::HORA_CIERRE);

        // Fusionar bloques solapados para evitar doble conteo
        $fusionados = $this->fusionarBloques($ocupados, $fecha);

        $slots  = [];
        $cursor = $apertura->copy();

        foreach ($fusionados as $bloque) {
            $bloqueInicio = Carbon::parse($fecha . ' ' . $bloque['inicio']);
            $bloqueFin    = Carbon::parse($fecha . ' ' . $bloque['fin']);

            // Generar slots entre cursor y el inicio del bloque
            while ($cursor->copy()->addMinutes($duracionMin)->lte($bloqueInicio)) {
                $slots[] = [
                    'inicio' => $cursor->copy(),
                    'fin'    => $cursor->copy()->addMinutes($duracionMin),
                ];
                $cursor->addMinutes($duracionMin);
            }

            // Mover cursor al fin del bloque si es necesario
            if ($cursor->lt($bloqueFin)) {
                $cursor = $bloqueFin->copy();
            }
        }

        // Generar slots después del último bloque hasta el cierre
        while ($cursor->copy()->addMinutes($duracionMin)->lte($cierre)) {
            $slots[] = [
                'inicio' => $cursor->copy(),
                'fin'    => $cursor->copy()->addMinutes($duracionMin),
            ];
            $cursor->addMinutes($duracionMin);
        }

        return $slots;
    }

    /**
     * Fusiona bloques de tiempo solapados en bloques continuos.
     *
     * @param array  $bloques [['inicio'=>'HH:MM','fin'=>'HH:MM'], ...]
     * @param string $fecha
     * @return array
     */
    private function fusionarBloques(array $bloques, string $fecha): array
    {
        if (empty($bloques)) {
            return [];
        }

        $fusionados = [$bloques[0]];

        for ($i = 1; $i < count($bloques); $i++) {
            $ultimo = &$fusionados[count($fusionados) - 1];

            if ($bloques[$i]['inicio'] <= $ultimo['fin']) {
                // Solapamiento → extender el bloque
                if ($bloques[$i]['fin'] > $ultimo['fin']) {
                    $ultimo['fin'] = $bloques[$i]['fin'];
                }
            } else {
                $fusionados[] = $bloques[$i];
            }
        }

        return $fusionados;
    }

    /**
     * Intercala los slots de cada espacio en round-robin para
     * distribuir la carga entre canchas.
     *
     * @param array $slotsPorEspacio
     * @return array
     */
    private function intercalarSlots(array $slotsPorEspacio): array
    {
        $resultado = [];
        $indices   = array_fill_keys(array_keys($slotsPorEspacio), 0);
        $total     = array_sum(array_map('count', $slotsPorEspacio));

        while (count($resultado) < $total) {
            foreach ($slotsPorEspacio as $idEspacio => $slots) {
                if ($indices[$idEspacio] < count($slots)) {
                    $resultado[] = $slots[$indices[$idEspacio]];
                    $indices[$idEspacio]++;
                }
            }
        }

        return $resultado;
    }
}
