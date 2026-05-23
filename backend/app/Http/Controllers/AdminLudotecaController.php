<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\TurnosLudoteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RegistrosLudoteca;
use App\Models\HistorialLudoteca;
use App\Models\MiembrosFamiliares;
use App\Models\SocioTitular;

class AdminLudotecaController extends Controller
{
    public function store(Request $request)
    {
        $instructor = Instructor::find($request->id_instructor);

        $tieneLudoteca = $instructor->disciplinas()
            ->where('disciplinas.id_disciplina', 26)
            ->exists();
        if (!$tieneLudoteca) {
            return response()->json([
                'status' => false,
                'message' => 'Este instructor no tiene permiso para impartir clases de ludoteca',
            ], 409);
        }
        $available = InstructorAvailabilityService::validarDisponibilidad($request->id_instructor, $request->fecha, $request->hora_inicio, $request->hora_fin);
        if ($available) {
            return response()->json([
                'status' => false,
                'message' => 'Instructor no disponible',
            ], 409);
        }
        TurnosLudoteca::create([
            'id_instructor' => $request->id_instructor,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,

        ]);
        return response()->json([
            'status' => true,
            'message' => 'Turno creado exitosamente'
        ], 201);
    }

    public function getInstructores()
    {
        $instructores = DB::table('instructores as i')
            ->join('instructor_disciplina as id', 'i.id_instructor', '=', 'id.id_instructor')
            ->where('id.id_disciplina', 26)
            ->select('i.id_instructor', 'i.nombre_completo')
            ->orderBy('i.nombre_completo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $instructores
        ]);
    }

    public function getTurnos()
    {
        $timezone = 'America/Mexico_City';
        $turnos = DB::table('turnos_ludoteca as t')
            ->join('instructores as i', 't.id_instructor', '=', 'i.id_instructor')
            ->whereBetween('t.fecha', [
                now($timezone)->toDateString(),
                now($timezone)->addDays(6)->toDateString()
            ])
            ->orderBy('t.fecha')
            ->orderBy('t.hora_inicio')
            ->select(
                't.id_turno',
                't.fecha',
                't.hora_inicio',
                't.hora_fin',
                'i.nombre_completo as instructor',
                'i.id_instructor'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $turnos
        ]);
    }
    //SDH 220: 
    public function getSociosConMenores()
    {
        $socios = SocioTitular::query()
            ->from('historial_ludoteca as m')
            ->join(
                'socios_titulares as s',
                'm.id_adulto',
                '=',
                's.id_socio'
            )
            ->select(
                's.id_socio',
                's.nombre_completo',
                's.numero_accion'
            )
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $socios
        ]);
    }

    //SDH 219
    public function getHistorial(Request $request)
    {
        $historial = HistorialLudoteca::query()
            ->join(
                'registros_ludoteca as l',
                'historial_ludoteca.id_registro_operativo',
                '=',
                'l.id_registro'
            )
            ->join(
                'miembros_familiares as m',
                'l.id_menor',
                '=',
                'm.id_miembro'
            )
            ->join(
                'socios_titulares as s',
                'm.socio_id',
                '=',
                's.id_socio'
            )
            ->select(
                'historial_ludoteca.id_historial',
                'historial_ludoteca.id_registro_operativo',
                'm.nombre_completo as nombre_menor',
                's.nombre_completo as nombre_titular',
                's.id_socio',
                's.numero_accion',
                'historial_ludoteca.hora_ingreso',
                'historial_ludoteca.hora_egreso',
                'historial_ludoteca.tiempo_total_minutos',
                'historial_ludoteca.calificacion_servicio',
                'historial_ludoteca.estatus_final',
                'historial_ludoteca.comentarios_padre'
            );

        // filtro fechas
        if ($request->fecha_inicio) {
            $historial->where('l.hora_ingreso', '>=', $request->fecha_inicio . ' 00:00:00');
        }
        if ($request->fecha_fin) {
            $historial->where('l.hora_ingreso', '<=', $request->fecha_fin . ' 23:59:59');
        }

        // filtro socio
        if ($request->socio_id) {
            $socio = SocioTitular::find($request->socio_id);
            if (!$socio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Socio no encontrado',
                ], 404);
            }
            $historial->where('s.id_socio', $request->socio_id);
        }

        if ($request->has('calificacion')) {

            if ($request->calificacion < 1 || $request->calificacion > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Calificacion debe ser entre 1 y 5',
                ], 400);
            }

            $historial->where(
                'historial_ludoteca.calificacion_servicio',
                $request->calificacion
            );
        }

        // filtro estatus
        if ($request->estatus) {
            $estatus_validos = ['COMPLETADA_CON_RETRASO', 'FORZADO_POR_SISTEMA', 'COMPLETADA_A_TIEMPO'];
            if (!in_array($request->estatus, $estatus_validos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estatus no valido',
                ], 400);
            }
            $historial->where(
                'historial_ludoteca.estatus_final',
                $request->estatus
            );
        }
        // filtro tiempo estancia
        if ($request->tiempo_estancia) {

            switch ($request->tiempo_estancia) {

                case 'lt15':
                    $historial->where(
                        'historial_ludoteca.tiempo_total_minutos',
                        '<',
                        15
                    );
                    break;

                case 'lt30':
                    $historial->whereBetween(
                        'historial_ludoteca.tiempo_total_minutos',
                        [15, 30]
                    );
                    break;

                case 'lt60':
                    $historial->whereBetween(
                        'historial_ludoteca.tiempo_total_minutos',
                        [31, 60]
                    );
                    break;

                case 'lt90':
                    $historial->whereBetween(
                        'historial_ludoteca.tiempo_total_minutos',
                        [61, 90]
                    );
                    break;

                case 'gt90':
                    $historial->where(
                        'historial_ludoteca.tiempo_total_minutos',
                        '>',
                        90
                    );
                    break;
            }
        }

        $resultado = $historial->get();

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
    public function getStats(Request $request)
    {
        $rango = $request->query('rango', 'hoy');

        $timezone = 'America/Mexico_City';
        switch ($rango) {
            case 'semana':
                $inicio = now($timezone)->startOfWeek();
                $fin = now($timezone)->endOfWeek();
                break;

            case 'mes':
                $inicio = now($timezone)->startOfMonth();
                $fin = now($timezone)->endOfMonth();
                break;

            default:
                $inicio = now($timezone)->startOfDay();
                $fin = now($timezone)->endOfDay();
                break;
        }

        // historial base
        $historialQuery = HistorialLudoteca::whereBetween(
            'hora_ingreso',
            [$inicio, $fin]
        );

        /*
        -------------------------
        KPI 1: Número niños
        -------------------------
        */
        $numeroNinos = (clone $historialQuery)->count();

        if ($rango === 'hoy') {
            $activos = RegistrosLudoteca::where(
                'estatus_ludoteca',
                'ACTIVA'
            )->count();

            $numeroNinos += $activos;
        }

        /*
        -------------------------
        KPI 2: Calificación promedio
        -------------------------
        */
        $calificacionPromedio = (clone $historialQuery)
            ->whereNotNull('calificacion_servicio')
            ->avg('calificacion_servicio');

        /*
        -------------------------
        KPI 3: Incidencias
        -------------------------
        */
        $incidencias = (clone $historialQuery)
            ->whereIn('estatus_final', [
                'COMPLETADA_CON_RETRASO',
                'FORZADO_POR_SISTEMA'
            ])
            ->count();

        /*
        -------------------------
        KPI 4: Tiempo promedio
        -------------------------
        */
        $tiempoPromedio = (clone $historialQuery)
            ->whereNotNull('tiempo_total_minutos')
            ->avg('tiempo_total_minutos');

        /*
        -------------------------
        Grafica 1
        -------------------------
        */
        if ($rango === 'hoy') {
            $afluencia = (clone $historialQuery)
                ->selectRaw('EXTRACT(HOUR FROM hora_ingreso) as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            $tiempoUso = (clone $historialQuery)
                ->selectRaw('EXTRACT(HOUR FROM hora_ingreso) as label, AVG(tiempo_total_minutos) as total')
                ->whereNotNull('tiempo_total_minutos')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        } else {
            $afluencia = (clone $historialQuery)
                ->selectRaw('DATE(hora_ingreso) as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            $tiempoUso = (clone $historialQuery)
                ->selectRaw('DATE(hora_ingreso) as label, AVG(tiempo_total_minutos) as total')
                ->whereNotNull('tiempo_total_minutos')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }

        /*
        -------------------------
        Grafica 2
        -------------------------ss
        */
        $calificaciones = (clone $historialQuery)
            ->selectRaw('calificacion_servicio as estrella, COUNT(*) as total')
            ->whereNotNull('calificacion_servicio')
            ->groupBy('calificacion_servicio')
            ->orderBy('calificacion_servicio')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'numero_ninos' => $numeroNinos,
                    'calificacion_promedio' => round($calificacionPromedio, 1),
                    'total_incidencias' => $incidencias,
                    'tiempo_promedio_min' => round($tiempoPromedio)
                ],
                'graficas' => [
                    'afluencia_temporal' => [
                        'labels' => $afluencia->pluck('label'),
                        'data' => $afluencia->pluck('total')
                    ],
                    'calificaciones' => [
                        'labels' => $calificaciones->pluck('estrella'),
                        'data' => $calificaciones->pluck('total')
                    ],
                    'tiempo_uso' => [
                        'labels' => $tiempoUso->pluck('label'),
                        'data' => $tiempoUso->map(function ($item) {
                            return round($item->total);
                        })
                    ]
                ]
            ]
        ]);
    }



}