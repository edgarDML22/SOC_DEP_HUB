<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\EspacioFisico;
use App\Models\SocioTitular;
use Carbon\Carbon;
use App\Models\Disciplina;
use Illuminate\Support\Facades\DB;
//SDH 225
class ReservationAdminController extends Controller
{

    public function index(Request $request)
    {
        $reservaciones = Reservacion::query()
            ->join(
                'espacios_fisicos as e',
                'reservaciones_on_demand.id_espacio',
                '=',
                'e.id_espacio'
            )
            ->join(
                'disciplinas as d',
                'reservaciones_on_demand.id_disciplina',
                '=',
                'd.id_disciplina'
            )
            ->join(
                'socios_titulares as s',
                'reservaciones_on_demand.id_socio_titular',
                '=',
                's.id_socio'
            )
            ->select(
                'reservaciones_on_demand.id_reserva as id_reserva',
                's.nombre_completo as nombre_titular',
                's.numero_accion',
                'e.nombre_espacio',
                'd.nombre_disciplina',

                DB::raw("
                    CASE
                        WHEN reservaciones_on_demand.acompanantes_draft IS NULL
                            OR reservaciones_on_demand.acompanantes_draft = '[]'
                        THEN 'INDIVIDUAL'
                        ELSE 'ACOMPANANTES'
                    END as modalidad
                "),

                'reservaciones_on_demand.fecha_reserva as fecha_reserva',
                'reservaciones_on_demand.hora_inicio',
                'reservaciones_on_demand.hora_fin',
                'reservaciones_on_demand.estatus_operativo',
                'reservaciones_on_demand.acompanantes_draft'
            )
            ->orderBy('reservaciones_on_demand.estatus_operativo');

        // filtro fechas
        if ($request->fecha_inicio && $request->fecha_fin) {

            $reservaciones->whereBetween(
                'reservaciones_on_demand.fecha_reserva',
                [
                    $request->fecha_inicio,
                    $request->fecha_fin
                ]
            );
        }

        // filtro socio
        if ($request->has('socio_id')) {

            $socio = SocioTitular::find($request->socio_id);

            if (!$socio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Socio no encontrado',
                ], 404);
            }

            $reservaciones->where(
                's.id_socio',
                $request->socio_id
            );
        }

        // filtro espacio
        if ($request->has('espacio_id')) {

            $espacio = EspacioFisico::find($request->espacio_id);

            if (!$espacio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Espacio no encontrado',
                ], 404);
            }

            $reservaciones->where(
                'reservaciones_on_demand.id_espacio',
                $request->espacio_id
            );
        }

        // filtro disciplina
        if ($request->has('disciplina_id')) {

            $disciplina = Disciplina::find($request->disciplina_id);

            if (!$disciplina) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disciplina no encontrada',
                ], 404);
            }

            $reservaciones->where(
                'reservaciones_on_demand.id_disciplina',
                $request->disciplina_id
            );
        }

        // filtro estatus
        if ($request->has('estatus_operativo')) {
            if ($request->estatus_operativo) {
                $estatus_validos = ['CANCELADA', 'CONFIRMADA', 'FINALIZADA', 'NO_SHOW', 'PENDIENTE', 'ACTIVA'];
                if (!in_array($request->estatus_operativo, $estatus_validos)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Estatus no valido',
                    ], 400);
                }

                $reservaciones->where(
                    'reservaciones_on_demand.estatus_operativo',
                    $request->estatus_operativo
                );
            }


        }
        $resultado = $reservaciones->get();

        if ($resultado->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay reservaciones encontradas'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);

    }

    //SDH 226                
    public function filterMeta()
    {
        $espacios = Reservacion::query()
            ->join(
                'espacios_fisicos as e',
                'reservaciones_on_demand.id_espacio',
                '=',
                'e.id_espacio'
            )
            ->select(
                'e.id_espacio',
                'e.nombre_espacio as nombre'
            )
            ->distinct()
            ->get();

        $socios = Reservacion::query()
            ->join(
                'socios_titulares as s',
                'reservaciones_on_demand.id_socio_titular',
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

        $disciplinas = Reservacion::query()
            ->join(
                'disciplinas as d',
                'reservaciones_on_demand.id_disciplina',
                '=',
                'd.id_disciplina'
            )
            ->select(
                'd.id_disciplina',
                'd.nombre_disciplina as nombre'
            )
            ->distinct()
            ->get();

        return response()->json([
            "success" => true,
            "data" => [
                "espacios" => $espacios,
                "socios" => $socios,
                "disciplinas" => $disciplinas
            ]
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

        $reservacionesQuery = Reservacion::whereBetween(
            'fecha_reserva',
            [
                $inicio->toDateString(),
                $fin->toDateString()
            ]
        );

        //KPI 1
        if ($rango === 'hoy') {

            $reservasPorTiempo = (clone $reservacionesQuery)
                ->selectRaw('EXTRACT(HOUR FROM hora_inicio) as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();

        } else {

            $reservasPorTiempo = (clone $reservacionesQuery)
                ->selectRaw('DATE(fecha_reserva) as label, COUNT(*) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }

        //KPI 2
        $estatus = (clone $reservacionesQuery)
            ->selectRaw('estatus_operativo as estatus, COUNT(*) as total')
            ->groupBy('estatus_operativo')
            ->orderBy('estatus_operativo')
            ->get();


        //KPI 3
        $reservacionesConAcompanantes = (clone $reservacionesQuery)
            ->whereNotNull('acompanantes_draft')
            ->where('acompanantes_draft', '!=', '[]')
            ->get();

        $conteoTipos = [
            'Amigo' => 0,
            'Invitado' => 0,
            'Familiar' => 0
        ];

        foreach ($reservacionesConAcompanantes as $reservacion) {

            $acompanantes = $reservacion->acompanantes_draft;

            if (!is_array($acompanantes)) {
                continue;
            }

            foreach ($acompanantes as $acompanante) {

                if (
                    isset($acompanante['tipo']) &&
                    array_key_exists($acompanante['tipo'], $conteoTipos)
                ) {

                    $conteoTipos[$acompanante['tipo']]++;
                }
            }
        }

        $total = array_sum($conteoTipos);

        if ($total > 0) {

            $porcentajes = [
                round(($conteoTipos['Amigo'] / $total) * 100),
                round(($conteoTipos['Invitado'] / $total) * 100),
                round(($conteoTipos['Familiar'] / $total) * 100)
            ];

        } else {

            $porcentajes = [0, 0, 0];
        }

        return response()->json([
            'success' => true,
            'data' => [

                'kpi1_reservas_por_dia' => [
                    'labels' => $reservasPorTiempo->pluck('label'),
                    'data' => $reservasPorTiempo->pluck('total')
                ],

                'kpi2_por_estatus' => [
                    'labels' => $estatus->pluck('estatus'),
                    'data' => $estatus->pluck('total')
                ],

                'kpi3_composicion_acompanantes' => [
                    'labels' => [
                        'Socios/Amigos',
                        'Invitados',
                        'Familiares'
                    ],
                    'data' => $porcentajes
                ]
            ]
        ]);
    }

}


