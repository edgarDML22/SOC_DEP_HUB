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
        // Revisamos otras reservas con fecha de hoy y con el mismo $id_espacio
        $id_socio = $request->user()->user_id;  

        $reservas = Reservacion::where('id_espacio', $id_espacio)
            ->where('fecha_reserva', $fecha) // <- Corregido: usamos $fecha
            ->where(function ($q) use ($id_socio){
                $q->where('estatus_operativo', 'ACTIVA') // Reservas firmes
                    ->orWhere(function ($sub) use ($id_socio) {
                        $sub->where('estatus_operativo', 'PENDIENTE')
                            ->where('fecha_expiracion', '>', now()) // PENDIENTES vivas
                            ->where('id_socio_titular', '!=', $id_socio);
                            });
            })
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





        // PASO 3: Encuentros de torneos en este espacio
        $encuentros = \Illuminate\Support\Facades\DB::table('encuentros_torneo')
            ->where('id_espacio', $id_espacio)
            ->whereDate('fecha_hora_inicio', $fecha)
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO'])
            ->get()
            ->map(function ($encuentro) {
                return [
                    'inicio' => \Carbon\Carbon::parse($encuentro->fecha_hora_inicio)->format('H:i:s'),
                    'fin' => \Carbon\Carbon::parse($encuentro->fecha_hora_fin)->format('H:i:s'),
                    'tipo' => 'torneo'
                ];
            });
        $resultado = $resultado->concat($encuentros);

        // PASO 4: Conflictos Personales (Inhabilitar si el usuario ya tiene actividades en otro lado a esa hora)
        // 4a. Reservas del usuario en otros espacios
        $misReservas = Reservacion::where('id_socio_titular', $id_socio)
            ->where('fecha_reserva', $fecha)
            ->where('id_espacio', '!=', $id_espacio) // No duplicar lo del paso 1
            ->whereIn('estatus_operativo', ['ACTIVA', 'PENDIENTE'])
            ->get()
            ->map(function ($reserva) {
                return [
                    'inicio' => $reserva->hora_inicio,
                    'fin' => $reserva->hora_fin,
                    'tipo' => 'conflicto_personal'
                ];
            });
        $resultado = $resultado->concat($misReservas);

        // 4b. Clases donde el usuario está inscrito
        $misClases = \Illuminate\Support\Facades\DB::table('inscripciones_clases')
            ->join('sesiones_activas', 'inscripciones_clases.id_sesion', '=', 'sesiones_activas.id_sesion')
            ->join('actividades_plantilla', 'sesiones_activas.id_actividad_plantilla', '=', 'actividades_plantilla.id_actividad_plantilla')
            ->where('inscripciones_clases.id_usuario', $id_socio)
            ->where('inscripciones_clases.tipo_usuario', 'socio_titular') // Ajustar según rol en el futuro
            ->where('sesiones_activas.fecha_sesion', $fecha)
            ->whereNotIn('inscripciones_clases.estatus_inscripcion', ['CANCELADA'])
            ->whereNotIn('sesiones_activas.estatus_sesion', ['CANCELADA', 'FINALIZADA'])
            ->get()
            ->map(function ($clase) {
                return [
                    'inicio' => $clase->hora_inicio,
                    'fin' => $clase->hora_fin,
                    'tipo' => 'conflicto_personal'
                ];
            });
        $resultado = $resultado->concat($misClases);

        // 4c. Torneos donde el usuario participa
        $misTorneos = \Illuminate\Support\Facades\DB::table('equipos_torneo')
            ->join('encuentros_torneo', function ($join) {
                $join->on('equipos_torneo.id_interno', '=', 'encuentros_torneo.id_competidor_1')
                     ->orOn('equipos_torneo.id_interno', '=', 'encuentros_torneo.id_competidor_2');
            })
            ->where('equipos_torneo.referencia_id', $id_socio)
            ->where('equipos_torneo.tipo_entidad', 'SOCIO_TITULAR')
            ->whereDate('encuentros_torneo.fecha_hora_inicio', $fecha)
            ->whereNotIn('encuentros_torneo.estatus_encuentro', ['CANCELADO', 'FINALIZADO'])
            ->get()
            ->map(function ($torneo) {
                return [
                    'inicio' => \Carbon\Carbon::parse($torneo->fecha_hora_inicio)->format('H:i:s'),
                    'fin' => \Carbon\Carbon::parse($torneo->fecha_hora_fin)->format('H:i:s'),
                    'tipo' => 'conflicto_personal'
                ];
            });
        $resultado = $resultado->concat($misTorneos);

        $resultado = $resultado->sortBy('inicio')->values();

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
}
