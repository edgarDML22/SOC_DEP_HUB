<?php

namespace App\Console\Commands;

use App\Models\PlantillaProgramacion;
use App\Models\SesionActiva;
use App\Services\PublicarProgramacionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class GenerarSesiones extends Command
{
    protected $signature = 'programacion:generar-sesiones
                            {--desde= : Fecha de inicio (Y-m-d). Por defecto: próximo lunes}
                            {--hasta= : Fecha de fin (Y-m-d). Por defecto: próximo domingo}
                            {--plantilla= : ID de plantilla específica. Por defecto: todas las ACTIVO}';

    protected $description = 'Proyecta sesiones_activas a partir de actividades_plantilla para un rango de fechas';

    public function __construct(private readonly PublicarProgramacionService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $desde = $this->option('desde')
            ? Carbon::createFromFormat('Y-m-d', $this->option('desde'))->startOfDay()
            : Carbon::today('America/Mexico_City')->next(Carbon::MONDAY)->startOfDay();

        $hasta = $this->option('hasta')
            ? Carbon::createFromFormat('Y-m-d', $this->option('hasta'))->endOfDay()
            : $desde->copy()->addDays(6)->endOfDay();

        $idPlantilla = $this->option('plantilla');

        $query = PlantillaProgramacion::withoutGlobalScopes()
            ->where('estatus_plantilla', true)
            ->with(['actividades' => fn($q) => $q->where('estatus', 'ACTIVO')]);

        if ($idPlantilla) {
            $query->where('id_plantilla', $idPlantilla);
        }

        $plantillas = $query->get();

        if ($plantillas->isEmpty()) {
            $this->warn('No se encontraron plantillas ACTIVO para procesar.');
            return self::SUCCESS;
        }

        // Trae todas las sesiones ya existentes en el rango con una sola query,
        // evitando el N+1 del loop .exists() anterior.
        $existentes = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [$desde->toDateString(), $hasta->toDateString()])
            ->pluck('fecha_sesion', 'id_actividad_plantilla')
            ->all();

        $totalInsertadas = 0;
        $periodo = CarbonPeriod::create($desde, $hasta);

        foreach ($plantillas as $plantilla) {
            // Reutiliza proyectarSesiones() del Service para cada semana del período.
            // Si el rango abarca varias semanas, itera semana a semana para que el
            // cálculo de fecha real por dia_semana sea correcto en cada una.
            foreach ($this->semanasDentro($periodo) as $rango) {
                $rows = $this->service->proyectarSesiones($plantilla->actividades, $rango);

                // Filtra duplicados en memoria usando el set de existentes ya cargado.
                $rows = array_filter($rows, function (array $row) use ($existentes): bool {
                    $key = $row['id_actividad_plantilla'];
                    return !isset($existentes[$key])
                        || $existentes[$key] !== $row['fecha_sesion'];
                });

                $rows = array_values($rows);

                if (!empty($rows)) {
                    SesionActiva::insert($rows);
                    $totalInsertadas += count($rows);

                    // Agrega las recién insertadas al set para evitar colisiones inter-semana.
                    foreach ($rows as $row) {
                        $existentes[$row['id_actividad_plantilla']] = $row['fecha_sesion'];
                    }
                }
            }
        }

        $this->info("Sesiones generadas: {$totalInsertadas} (rango {$desde->toDateString()} → {$hasta->toDateString()})");

        return self::SUCCESS;
    }

    /**
     * Divide un CarbonPeriod en rangos semana-a-semana (lunes–domingo).
     * Permite que proyectarSesiones() opere con la semana correcta en cada iteración.
     *
     * @return iterable<array{lunes: Carbon, domingo: Carbon}>
     */
    private function semanasDentro(CarbonPeriod $periodo): iterable
    {
        $cursor = $periodo->getStartDate()->copy()->startOfWeek(Carbon::MONDAY);
        $fin    = $periodo->getEndDate()->copy();

        while ($cursor->lte($fin)) {
            yield $this->service->calcularRangoSemana($cursor->toDateString());
            $cursor->addWeek();
        }
    }
}
