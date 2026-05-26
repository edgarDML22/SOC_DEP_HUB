<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reservacion;
use App\Models\RegistrosLudoteca;
use App\Models\HistorialLudoteca;
use App\Models\SocioTitular;
use App\Models\Torneo;
use App\Models\ParticipantesTorneo;
use App\Models\SesionActiva;
use App\Models\ActividadPlantilla;
use App\Models\RegistroAsistencia;
use App\Models\EspacioFisico;
use App\Models\Disciplina;
use Carbon\Carbon;

class BIController extends Controller
{
    /**
     * Endpoint 1: Home Page Dashboard Stats (HOY)
     */
    public function getDashboardStats()
    {
        $timezone = 'America/Mexico_City';
        $hoy = now($timezone)->toDateString();

        // 1. Reservaciones de Hoy (Afluencia Operativa por Hora)
        $reservasPorHora = Reservacion::where('fecha_reserva', $hoy)
            ->where('estatus_operativo', '!=', 'CANCELADA')
            ->selectRaw('EXTRACT(HOUR FROM hora_inicio::time) as label, COUNT(*) as total')
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        // 2. Estatus Operativo (Dona)
        $estatusOperativo = Reservacion::where('fecha_reserva', $hoy)
            ->selectRaw('estatus_operativo as label, COUNT(*) as total')
            ->groupBy('label')
            ->get();

        // 3. Top 5 Espacios más Demandados Hoy
        $topEspacios = Reservacion::where('fecha_reserva', $hoy)
            ->join('espacios_fisicos as e', 'reservaciones_on_demand.id_espacio', '=', 'e.id_espacio')
            ->selectRaw('e.nombre_espacio as label, COUNT(*) as total')
            ->groupBy('e.nombre_espacio')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 4. Monitor de Ludoteca en Vivo (KPIs)
        $startOfDay = now($timezone)->startOfDay();
        $endOfDay = now($timezone)->endOfDay();

        $ninosActivos = RegistrosLudoteca::where('estatus_ludoteca', 'ACTIVA')->count();
        $ninosEntregados = RegistrosLudoteca::whereBetween('hora_ingreso', [$startOfDay, $endOfDay])
            ->whereIn('estatus_ludoteca', ['COMPLETADA_A_TIEMPO', 'COMPLETADA_CON_RETRASO'])
            ->count();

        // Calcular ingresos estimados de hoy ($1.5 MXN por minuto de uso)
        $ingresosHistorial = HistorialLudoteca::whereBetween('hora_ingreso', [$startOfDay, $endOfDay])
            ->whereNotNull('tiempo_total_minutos')
            ->sum('tiempo_total_minutos') * 1.5;

        $ingresosActivos = 0;
        $registrosActivos = RegistrosLudoteca::where('estatus_ludoteca', 'ACTIVA')->get();
        foreach ($registrosActivos as $reg) {
            if ($reg->hora_ingreso) {
                // Solo sumar ingresos si el ingreso fue hoy
                if (Carbon::parse($reg->hora_ingreso)->toDateString() === $hoy) {
                    $diffMinutos = Carbon::parse($reg->hora_ingreso)->diffInMinutes(now($timezone));
                    $ingresosActivos += $diffMinutos * 1.5;
                }
            }
        }
        $ingresosTotalesHoy = round($ingresosHistorial + $ingresosActivos, 2);

        // Alertas de tiempo excedido (> 90 minutos de estancia de ingresos de HOY)
        $alertasTiempoExcedido = 0;
        foreach ($registrosActivos as $reg) {
            if ($reg->hora_ingreso) {
                if (Carbon::parse($reg->hora_ingreso)->toDateString() === $hoy) {
                    $diffMinutos = Carbon::parse($reg->hora_ingreso)->diffInMinutes(now($timezone));
                    if ($diffMinutos > 90) {
                        $alertasTiempoExcedido++;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reservas_hoy_hora' => [
                    'labels' => $reservasPorHora->pluck('label')->map(fn($h) => sprintf('%02d:00', $h)),
                    'data' => $reservasPorHora->pluck('total')
                ],
                'estatus_operativo' => [
                    'labels' => $estatusOperativo->pluck('label'),
                    'data' => $estatusOperativo->pluck('total')
                ],
                'top_espacios' => [
                    'labels' => $topEspacios->pluck('label'),
                    'data' => $topEspacios->pluck('total')
                ],
                'ludoteca_kpis' => [
                    'ninos_activos' => $ninosActivos,
                    'ingresos_hoy' => $ingresosTotalesHoy,
                    'alertas_tiempo' => $alertasTiempoExcedido,
                    'ninos_entregados' => $ninosEntregados
                ]
            ]
        ]);
    }
    /**
     * Resuelve rango de fechas en la zona horaria America/Mexico_City.
     */
    private function resolveDates(Request $request)
    {
        $timezone = 'America/Mexico_City';
        $rango = $request->query('rango');

        if ($rango) {
            switch ($rango) {
                case 'hoy':
                    $inicio = now($timezone)->toDateString();
                    $fin = now($timezone)->toDateString();
                    break;
                case 'semana':
                    $inicio = now($timezone)->startOfWeek()->toDateString();
                    $fin = now($timezone)->endOfWeek()->toDateString();
                    break;
                case 'mes':
                    $inicio = now($timezone)->startOfMonth()->toDateString();
                    $fin = now($timezone)->endOfMonth()->toDateString();
                    break;
                default:
                    $inicio = $request->query('fecha_inicio', now($timezone)->startOfMonth()->toDateString());
                    $fin = $request->query('fecha_fin', now($timezone)->endOfMonth()->toDateString());
                    break;
            }
        } else {
            $inicio = $request->query('fecha_inicio', now($timezone)->startOfMonth()->toDateString());
            $fin = $request->query('fecha_fin', now($timezone)->endOfMonth()->toDateString());
        }

        return [$inicio, $fin];
    }

    /**
     * Endpoint 2: Clases e Instructores (Rendimiento Académico)
     */
    public function getAcademicPerformanceStats(Request $request)
    {
        list($fechaInicio, $fechaFin) = $this->resolveDates($request);
        $idDisciplina = $request->query('id_disciplina');
        $idInstructor = $request->query('id_instructor');

        // --- Gráfico 1: Matriz de Demanda (Horarios y Disciplinas) ---
        // Cruza dia_semana (1=Lunes, etc.), hora_inicio y disciplina para calcular el % promedio de asistencia frente al cupo
        $queryDemanda = DB::table('sesiones_activas as s')
            ->join('actividades_plantilla as ap', 's.id_actividad_plantilla', '=', 'ap.id_actividad_plantilla')
            ->join('disciplinas as d', 'ap.id_disciplina', '=', 'd.id_disciplina')
            ->selectRaw('ap.dia_semana, EXTRACT(HOUR FROM ap.hora_inicio::time) as hora, d.nombre_disciplina as disciplina, AVG(s.cantidad_inscritos) as avg_inscritos, AVG(ap.cupo_maximo) as avg_cupo')
            ->whereBetween('s.fecha_sesion', [$fechaInicio, $fechaFin]);

        if ($idDisciplina) {
            $queryDemanda->where('ap.id_disciplina', $idDisciplina);
        }
        if ($idInstructor) {
            $queryDemanda->where('ap.id_instructor', $idInstructor);
        }

        $demanda = $queryDemanda->groupBy('ap.dia_semana', 'hora', 'd.nombre_disciplina')
            ->orderBy('ap.dia_semana')
            ->orderBy('hora')
            ->get();

        // --- Gráfico 2: Ranking de Instructores (Convocatoria) ---
        // Llenado promedio de las clases de cada instructor (Alumnos inscritos vs cupo máximo)
        $queryInstructores = DB::table('sesiones_activas as s')
            ->join('actividades_plantilla as ap', 's.id_actividad_plantilla', '=', 'ap.id_actividad_plantilla')
            ->join('instructores as i', 'ap.id_instructor', '=', 'i.id_instructor')
            ->selectRaw('i.id_instructor, i.nombre_completo, AVG(CASE WHEN ap.cupo_maximo > 0 THEN (CAST(s.cantidad_inscritos AS FLOAT) / CAST(ap.cupo_maximo AS FLOAT) * 100) ELSE 0 END) as llenado_promedio')
            ->whereBetween('s.fecha_sesion', [$fechaInicio, $fechaFin]);

        if ($idDisciplina) {
            $queryInstructores->where('ap.id_disciplina', $idDisciplina);
        }
        if ($idInstructor) {
            $queryInstructores->where('ap.id_instructor', $idInstructor);
        }

        $rankingInstructores = $queryInstructores->groupBy('i.id_instructor', 'i.nombre_completo')
            ->orderByDesc('llenado_promedio')
            ->get();

        // --- Gráfico 3: Asistencia vs. Abandono por Disciplina ---
        // Compara Check-in (asistencia=true) vs No-Show (asistencia=false)
        $queryAsistencia = DB::table('registros_asistencia as ra')
            ->join('sesiones_activas as s', 'ra.id_sesion', '=', 's.id_sesion')
            ->join('actividades_plantilla as ap', 's.id_actividad_plantilla', '=', 'ap.id_actividad_plantilla')
            ->join('disciplinas as d', 'ap.id_disciplina', '=', 'd.id_disciplina')
            ->selectRaw('d.nombre_disciplina, SUM(CASE WHEN ra.asistencia = true THEN 1 ELSE 0 END) as asistencias, SUM(CASE WHEN ra.asistencia = false THEN 1 ELSE 0 END) as no_shows')
            ->whereBetween('s.fecha_sesion', [$fechaInicio, $fechaFin]);

        if ($idDisciplina) {
            $queryAsistencia->where('ap.id_disciplina', $idDisciplina);
        }
        if ($idInstructor) {
            $queryAsistencia->where('ap.id_instructor', $idInstructor);
        }

        $asistenciaDisciplina = $queryAsistencia->groupBy('d.nombre_disciplina')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'matriz_demanda' => $demanda->map(function ($row) {
                    $ratio = $row->avg_cupo > 0 ? round(($row->avg_inscritos / $row->avg_cupo) * 100, 1) : 0;
                    return [
                        'dia' => $row->dia_semana,
                        'hora' => sprintf('%02d:00', $row->hora),
                        'disciplina' => $row->disciplina,
                        'ocupacion_porcentaje' => $ratio,
                        'avg_inscritos' => round($row->avg_inscritos, 1)
                    ];
                }),
                'ranking_instructores' => [
                    'labels' => $rankingInstructores->pluck('nombre_completo'),
                    'data' => $rankingInstructores->map(fn($r) => round($r->llenado_promedio, 1))
                ],
                'asistencia_disciplina' => [
                    'labels' => $asistenciaDisciplina->pluck('nombre_disciplina'),
                    'asistencias' => $asistenciaDisciplina->pluck('asistencias'),
                    'no_shows' => $asistenciaDisciplina->pluck('no_shows')
                ]
            ]
        ]);
    }

    /**
     * Endpoint 3: Ocupación de Espacios e Infraestructura
     */
    public function getSpacesStats(Request $request)
    {
        list($fechaInicio, $fechaFin) = $this->resolveDates($request);
        $idEspacio = $request->query('id_espacio');

        // --- Gráfico 1: Mapa de Calor de Saturación General ---
        // Combinamos afluencia de reservas on-demand por día de la semana y bloque de hora
        $querySaturacion = Reservacion::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->where('estatus_operativo', '!=', 'CANCELADA');

        if ($idEspacio) {
            $querySaturacion->where('reservaciones_on_demand.id_espacio', $idEspacio);
        }

        $saturacion = $querySaturacion->selectRaw('
                EXTRACT(ISODOW FROM fecha_reserva::date) as dia_semana,
                EXTRACT(HOUR FROM hora_inicio::time) as hora,
                COUNT(*) as total
            ')
            ->groupBy('dia_semana', 'hora')
            ->orderBy('dia_semana')
            ->orderBy('hora')
            ->get();

        // --- Gráfico 2: Ocupación por Disciplina ---
        $queryOcupacion = Reservacion::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->where('estatus_operativo', '!=', 'CANCELADA')
            ->join('disciplinas as d', 'reservaciones_on_demand.id_disciplina', '=', 'd.id_disciplina')
            ->selectRaw('d.nombre_disciplina as label, d.id_disciplina, COUNT(*) as total')
            ->groupBy('d.nombre_disciplina', 'd.id_disciplina');

        if ($idEspacio) {
            $queryOcupacion->where('reservaciones_on_demand.id_espacio', $idEspacio);
        }

        $ocupacion = $queryOcupacion->get();

        // --- Gráfico 3: Ocupación por Espacio Físico (con filtro opcional de disciplina) ---
        $queryEspacio = Reservacion::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->where('estatus_operativo', '!=', 'CANCELADA')
            ->join('espacios_fisicos as e', 'reservaciones_on_demand.id_espacio', '=', 'e.id_espacio')
            ->selectRaw('e.nombre_espacio as label, COUNT(*) as total')
            ->groupBy('e.nombre_espacio')
            ->orderByDesc('total');

        if ($idEspacio) {
            $queryEspacio->where('reservaciones_on_demand.id_espacio', $idEspacio);
        }

        $idDisciplina = $request->query('id_disciplina');
        if ($idDisciplina) {
            $queryEspacio->where('reservaciones_on_demand.id_disciplina', $idDisciplina);
        }

        $ocupacionEspacio = $queryEspacio->get();

        return response()->json([
            'success' => true,
            'data' => [
                'saturacion_heatmap' => $saturacion->map(function ($row) {
                    return [
                        'dia' => intval($row->dia_semana),
                        'hora' => sprintf('%02d:00', $row->hora),
                        'total' => intval($row->total)
                    ];
                }),
                'ocupacion_por_tipo' => [
                    'labels' => $ocupacion->pluck('label'),
                    'ids' => $ocupacion->pluck('id_disciplina'),
                    'data' => $ocupacion->pluck('total')
                ],
                'ocupacion_por_espacio' => [
                    'labels' => $ocupacionEspacio->pluck('label'),
                    'data' => $ocupacionEspacio->pluck('total')
                ]
            ]
        ]);
    }

    /**
     * Endpoint 4: Auditoría, Fidelización y Sanciones
     */
    public function getAuditoriaStats(Request $request)
    {
        list($fechaInicio, $fechaFin) = $this->resolveDates($request);

        // --- Gráfico 1: Tasa de Abandono General (No-Shows) por Mes ---
        // Tendencia temporal de no-shows
        $tendenciaNoShows = Reservacion::whereBetween('fecha_reserva', [$fechaInicio, $fechaFin])
            ->where('estatus_operativo', 'NO_SHOW')
            ->selectRaw("TO_CHAR(fecha_reserva::date, 'YYYY-MM') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // --- Tabla A: Fidelización (Heavy Users vs. Fantasmas) ---
        // Heavy Users: Socios con mayor asistencia en el rango seleccionado
        $heavyUsers = DB::table('socios_titulares as s')
            ->leftJoin('registros_asistencia as ra', function ($join) use ($fechaInicio, $fechaFin) {
                $join->on('s.id_socio', '=', 'ra.id_usuario')
                    ->where('ra.tipo_usuario', '=', 'SOCIO_TITULAR')
                    ->where('ra.asistencia', '=', true)
                    ->whereBetween('ra.fecha_hora_registro', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->leftJoin('historial_ludoteca as hl', function ($join) use ($fechaInicio, $fechaFin) {
                $join->on('s.id_socio', '=', 'hl.id_adulto')
                    ->whereBetween('hl.hora_ingreso', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->selectRaw('s.id_socio, s.nombre_completo, s.numero_accion, COUNT(DISTINCT ra.id_registro) + COUNT(DISTINCT hl.id_historial) as total_asistencias')
            ->groupBy('s.id_socio', 's.nombre_completo', 's.numero_accion')
            ->orderByDesc('total_asistencias')
            ->limit(30)
            ->get();

        // Fantasmas: Socios activos con 0 asistencias
        $fantasmas = DB::table('socios_titulares as s')
            ->where('s.estatus_cuenta', 'AL_CORRIENTE')
            ->whereNotExists(function ($query) use ($fechaInicio, $fechaFin) {
                $query->select(DB::raw(1))
                    ->from('registros_asistencia as ra')
                    ->whereRaw('ra.id_usuario = s.id_socio')
                    ->where('ra.tipo_usuario', '=', 'SOCIO_TITULAR')
                    ->where('ra.asistencia', '=', true)
                    ->whereBetween('ra.fecha_hora_registro', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->whereNotExists(function ($query) use ($fechaInicio, $fechaFin) {
                $query->select(DB::raw(1))
                    ->from('reservaciones_on_demand as r')
                    ->whereRaw('r.id_socio_titular = s.id_socio')
                    ->where('r.estatus_operativo', '=', 'FINALIZADA')
                    ->whereBetween('r.fecha_reserva', [$fechaInicio, $fechaFin]);
            })
            ->select('s.id_socio', 's.nombre_completo', 's.numero_accion')
            ->limit(30)
            ->get();

        // --- Tabla B: La "Lista Negra" (Reincidentes) ---
        // Socios con mayor acumulado de No-Shows y retrasos de ludoteca
        $listaNegra = SocioTitular::select('id_socio', 'nombre_completo', 'numero_accion', 'contador_no_shows', 'retrasos_ludoteca', 'estatus_penalizacion')
            ->where(function($query) {
                $query->where('contador_no_shows', '>', 0)
                      ->orWhere('retrasos_ludoteca', '>', 0);
            })
            ->orderByRaw('(contador_no_shows + retrasos_ludoteca) DESC')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tendencia_no_shows' => [
                    'labels' => $tendenciaNoShows->pluck('mes'),
                    'data' => $tendenciaNoShows->pluck('total')
                ],
                'heavy_users' => $heavyUsers,
                'fantasmas' => $fantasmas,
                'lista_negra' => $listaNegra
            ]
        ]);
    }

    /**
     * Endpoint 5: Analítica de Torneos
     */
    public function getTournamentsStats(Request $request)
    {
        $timezone = 'America/Mexico_City';
        list($fechaInicio, $fechaFin) = $this->resolveDates($request);
        $idTorneo = $request->query('id_torneo');
        $idDisciplina = $request->query('id_disciplina');

        // --- Gráfico 1: Inscripciones por Categoría ---
        $queryInscritosCat = DB::table('participantes_torneo as pt')
            ->join('torneos as t', 'pt.id_torneo', '=', 't.id_torneo')
            ->join('categorias_torneo as ct', 't.id_categoria', '=', 'ct.id_categoria')
            ->selectRaw('ct.nombre_categoria, COUNT(*) as total')
            ->whereBetween('t.fecha_inicio', [$fechaInicio, $fechaFin]);

        if ($idTorneo) {
            $queryInscritosCat->where('t.id_torneo', $idTorneo);
        }
        if ($idDisciplina) {
            $queryInscritosCat->where('t.id_disciplina', $idDisciplina);
        }

        $inscritosCat = $queryInscritosCat->groupBy('ct.nombre_categoria')->get();

        // --- Gráfico 2: Origen de los Competidores ---
        $queryOrigen = DB::table('participantes_torneo as pt')
            ->join('torneos as t', 'pt.id_torneo', '=', 't.id_torneo')
            ->selectRaw('pt.tipo_entidad, COUNT(*) as total')
            ->whereBetween('t.fecha_inicio', [$fechaInicio, $fechaFin]);

        if ($idTorneo) {
            $queryOrigen->where('t.id_torneo', $idTorneo);
        }
        if ($idDisciplina) {
            $queryOrigen->where('t.id_disciplina', $idDisciplina);
        }

        $origen = $queryOrigen->groupBy('pt.tipo_entidad')->get();

        // --- Gráfico 3: Participación por Disciplina (Histórico Anual) ---
        $queryHistorico = DB::table('participantes_torneo as pt')
            ->join('torneos as t', 'pt.id_torneo', '=', 't.id_torneo')
            ->join('disciplinas as d', 't.id_disciplina', '=', 'd.id_disciplina')
            ->selectRaw("d.nombre_disciplina, TO_CHAR(pt.fecha_inscripcion::date, 'YYYY-MM') as mes, COUNT(*) as total")
            ->whereBetween('pt.fecha_inscripcion', [now($timezone)->subYear()->toDateString(), now($timezone)->toDateString()]);

        if ($idDisciplina) {
            $queryHistorico->where('t.id_disciplina', $idDisciplina);
        }

        $historico = $queryHistorico->groupBy('d.nombre_disciplina', 'mes')
            ->orderBy('mes')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'inscripciones_categoria' => [
                    'labels' => $inscritosCat->pluck('nombre_categoria'),
                    'data' => $inscritosCat->pluck('total')
                ],
                'origen_competidores' => [
                    'labels' => $origen->pluck('tipo_entidad')->map(function($tipo) {
                        return str_replace('_', ' ', $tipo ?: 'Desconocido');
                    }),
                    'data' => $origen->pluck('total')
                ],
                'historico_disciplina' => $historico->groupBy('nombre_disciplina')->map(function($rows) {
                    return [
                        'labels' => $rows->pluck('mes'),
                        'data' => $rows->pluck('total')
                    ];
                })
            ]
        ]);
    }

    /**
     * Endpoint 6: Datos Contextuales de Socios (Demografía y Membresía)
     */
    public function getSocioDemographics()
    {
        // 1. Segmentación de Membresías (Accionistas vs Rentistas)
        $tipoMembresia = SocioTitular::selectRaw('tipo_socio, COUNT(*) as total')
            ->groupBy('tipo_socio')
            ->get();

        // 2. Modalidad de Planes (Individual vs Familiar)
        $modalidadPlan = SocioTitular::selectRaw('modalidad_plan, COUNT(*) as total')
            ->groupBy('modalidad_plan')
            ->get();

        // 3. Distribución Demográfica (Edad y Género)
        // Rangos: Menores 18, 18-29, 30-49, 50-64, 65+
        $demographics = SocioTitular::selectRaw("
            genero,
            CASE
                WHEN EXTRACT(YEAR FROM AGE(fecha_nacimiento)) < 18 THEN 'Menores 18'
                WHEN EXTRACT(YEAR FROM AGE(fecha_nacimiento)) BETWEEN 18 AND 29 THEN '18-29'
                WHEN EXTRACT(YEAR FROM AGE(fecha_nacimiento)) BETWEEN 30 AND 49 THEN '30-49'
                WHEN EXTRACT(YEAR FROM AGE(fecha_nacimiento)) BETWEEN 50 AND 64 THEN '50-64'
                ELSE '65+'
            END as rango_edad,
            COUNT(*) as total
        ")
        ->whereNotNull('fecha_nacimiento')
        ->groupBy('genero', 'rango_edad')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tipo_membresia' => [
                    'labels' => $tipoMembresia->pluck('tipo_socio')->map(fn($t) => ucfirst(strtolower($t))),
                    'data' => $tipoMembresia->pluck('total')
                ],
                'modalidad_plan' => [
                    'labels' => $modalidadPlan->pluck('modalidad_plan')->map(fn($m) => ucfirst(strtolower($m))),
                    'data' => $modalidadPlan->pluck('total')
                ],
                'demografia' => $demographics
            ]
        ]);
    }
}
