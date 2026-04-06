<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendaEspacioRequest;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaEspacioController extends Controller
{
    public function getScheduleForSpace(AgendaEspacioRequest $request): JsonResponse

    {
        $filtros = $request->validated();
        // Obtener id_espacio y fecha
        $fecha = $filtros['date'] ?? now()->toDateString();
        $id_espacio = $filtros['id_espacio'];

        $resultado = collect([]);

        // PASO 1
        // Revisamos otras reservas TABLE reservaciones_on_demand
        // con fecha de hoy y con el mismo $id_espacio
        // ver si hay una forma más eficiente de hacerlo con with()
        $reservas = Reservacion::where('id_espacio', $id_espacio)
            ->where('fecha_reserva', $fecha)
            ->whereNotIn('estatus_operativo', ['NO_SHOW', 'CANCELADA', 'FINALIZADA'])
            ->get()
            ->map(function ($reserva) {
                return [
                    'inicio' => $reserva->hora_inicio,
                    'fin' => $reserva->hora_fin,
                    'tipo' => 'reserva'
                ];
            });
        // se añaden las reservas
        $resultado = $resultado->concat($reservas);
        

        // PASO 2
        // Revisamos actividades programadas TABLE sesiones_activas
        // con fecha de hoy y el mismo $id_espacio
        // WHERE estatus_sesion != "FINALIZADA", "CANCELADA"
        // id_actividad_plantilla
        // el id_espacio, hora_inicio y hora_fin está en la tabla actividades_plantilla
        $sesiones = SesionActiva::where('fecha_sesion', $fecha)
            ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
            ->whereHas('actividadPlantilla', function ($query) use ($id_espacio) { // relacion sesiones_activas -> actividades_plantilla
                $query->where('id_espacio', $id_espacio);
            })
            //con with traigo todos los datos de cada actividad_plantilla
            //para que se cargen una sola vez
            ->with('actividadPlantilla')
            ->get()
            ->map(function ($sesion) {
                return [
                    'inicio' => $sesion->actividadPlantilla->hora_inicio,
                    'fin' => $sesion->actividadPlantilla->hora_fin,
                    'tipo' => 'sesion'
                ];
            });
        
        $resultado = $resultado->concat($sesiones);





        // PASO 3 ** PENDIENETE **
        // En el futuro revisar si hay partidos de Torneos: TABLE encuentros_torneo
        // con fecha de hoy y el mismo $id_espacio
        // $encuentros
        // $resultado = $resultado->concat($encuentros);

        //return $resultado->sortBy('inicio')->values()->toArray();

        $resultado = $resultado->sortBy('inicio')->values();

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
}
