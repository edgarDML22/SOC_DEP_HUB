<?php

namespace App\Console\Commands;

use App\Models\PlantillaProgramacion;
use App\Models\SesionActiva;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class GenerarSesiones extends Command
{
    protected $signature = 'programacion:generar-sesiones
                            {--desde= : Fecha de inicio (Y-m-d). Por defecto: hoy}
                            {--hasta= : Fecha de fin (Y-m-d). Por defecto: hoy + 14 días}
                            {--plantilla= : ID de plantilla específica. Por defecto: todas las ACTIVO}';

    protected $description = 'Proyecta sesiones_activas a partir de actividades_plantilla para un rango de fechas';

    // Mapa de dia_semana (texto) → número ISO (1=Lunes … 7=Domingo)
    private const DIA_ISO = [
        'LUNES'     => 1,
        'MARTES'    => 2,
        'MIERCOLES' => 3,
        'JUEVES'    => 4,
        'VIERNES'   => 5,
        'SABADO'    => 6,
        'DOMINGO'   => 7,
    ];

    public function handle(): int
    {
        $desde = $this->option('desde')
            ? Carbon::parse($this->option('desde'))
            : Carbon::today('America/Mexico_City');

        $hasta = $this->option('hasta')
            ? Carbon::parse($this->option('hasta'))
            : Carbon::today('America/Mexico_City')->addDays(14);

        $idPlantilla = $this->option('plantilla');

        $query = PlantillaProgramacion::withoutGlobalScopes()
            ->where('estatus_plantilla', 'ACTIVO')
            ->with(['actividades' => fn($q) => $q->where('estatus', 'ACTIVO')]);

        if ($idPlantilla) {
            $query->where('id_plantilla', $idPlantilla);
        }

        $plantillas = $query->get();

        if ($plantillas->isEmpty()) {
            $this->warn('No se encontraron plantillas ACTIVO para procesar.');
            return self::SUCCESS;
        }

        $totalInsertadas = 0;
        $periodo = CarbonPeriod::create($desde, $hasta);

        foreach ($plantillas as $plantilla) {
            foreach ($plantilla->actividades as $actividad) {
                $diaISO = self::DIA_ISO[$actividad->dia_semana] ?? null;
                if ($diaISO === null) {
                    $this->warn("dia_semana desconocido: {$actividad->dia_semana} (actividad {$actividad->id_actividad_plantilla})");
                    continue;
                }

                $sesiones = [];
                foreach ($periodo as $fecha) {
                    if ($fecha->dayOfWeekIso !== $diaISO) {
                        continue;
                    }

                    $fechaStr = $fecha->toDateString();

                    // Evitar duplicados: si la sesión ya existe para esa actividad + fecha, saltar
                    $existe = SesionActiva::withoutGlobalScopes()
                        ->where('id_actividad_plantilla', $actividad->id_actividad_plantilla)
                        ->where('fecha_sesion', $fechaStr)
                        ->exists();

                    if (!$existe) {
                        $sesiones[] = [
                            'id_actividad_plantilla' => $actividad->id_actividad_plantilla,
                            'fecha_sesion'           => $fechaStr,
                            'estatus_sesion'         => 'DISPONIBLE',
                            'cantidad_inscritos'     => 0,
                        ];
                    }
                }

                if (!empty($sesiones)) {
                    SesionActiva::insert($sesiones);
                    $totalInsertadas += count($sesiones);
                }
            }
        }

        $this->info("Sesiones generadas: {$totalInsertadas} (rango {$desde->toDateString()} → {$hasta->toDateString()})");

        return self::SUCCESS;
    }
}
