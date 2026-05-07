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

}


