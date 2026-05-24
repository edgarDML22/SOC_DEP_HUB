<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendaEspacioRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AgendaEspacioController extends Controller
{
    public function getScheduleForSpace(AgendaEspacioRequest $request): JsonResponse
    {
        $filtros    = $request->validated();
        $fecha      = $filtros['date'] ?? now()->toDateString();
        $id_espacio = $filtros['id_espacio'];
        $id_socio   = $request->user()->user_id;
        $ahora      = now('America/Mexico_City');

        $bloques = DB::select("
            SELECT hora_inicio AS inicio, hora_fin AS fin, 'reserva' AS tipo
            FROM   reservaciones_on_demand
            WHERE  id_espacio = ?
              AND  fecha_reserva = ?
              AND (
                    estatus_operativo = 'ACTIVA'
                    OR (estatus_operativo = 'PENDIENTE' AND fecha_expiracion > ? AND id_socio_titular != ?)
                  )

            UNION ALL

            SELECT ap.hora_inicio, ap.hora_fin, 'sesion' AS tipo
            FROM   sesiones_activas sa
            JOIN   actividades_plantilla ap ON ap.id_actividad_plantilla = sa.id_actividad_plantilla
            WHERE  sa.fecha_sesion = ?
              AND  ap.id_espacio   = ?
              AND  sa.estatus_sesion NOT IN ('CANCELADA', 'FINALIZADA')

            -- PASO 3: Encuentros de torneo en este espacio
            UNION ALL
            SELECT CAST(fecha_hora_inicio AS TIME), CAST(fecha_hora_fin AS TIME), 'torneo' AS tipo
            FROM   encuentros_torneo
            WHERE  id_espacio = ?
              AND  CAST(fecha_hora_inicio AS DATE) = ?
              AND  fecha_hora_inicio IS NOT NULL
              AND  fecha_hora_fin IS NOT NULL
              AND  estatus_encuentro NOT IN ('CANCELADO', 'FINALIZADO')

            UNION ALL

            SELECT hora_inicio, hora_fin, 'conflicto_personal' AS tipo
            FROM   reservaciones_on_demand
            WHERE  id_socio_titular = ?
              AND  fecha_reserva    = ?
              AND  id_espacio      != ?
              AND  estatus_operativo IN ('ACTIVA', 'PENDIENTE')

            UNION ALL

            SELECT ap.hora_inicio, ap.hora_fin, 'conflicto_personal' AS tipo
            FROM   inscripciones_clases ic
            JOIN   sesiones_activas sa  ON sa.id_sesion = ic.id_sesion
            JOIN   actividades_plantilla ap ON ap.id_actividad_plantilla = sa.id_actividad_plantilla
            WHERE  ic.id_usuario = ?
              AND  ic.tipo_usuario = 'socio_titular'
              AND  sa.fecha_sesion = ?
              AND  ic.estatus_inscripcion != 'CANCELADA'
              AND  sa.estatus_sesion NOT IN ('CANCELADA', 'FINALIZADA')

            UNION ALL

            SELECT CAST(et.fecha_hora_inicio AS TIME), CAST(et.fecha_hora_fin AS TIME), 'conflicto_personal' AS tipo
            FROM   encuentros_torneo et
            JOIN   participantes_torneo pt ON (
                       (et.competidor_1_type = 'PARTICIPANTE' AND et.competidor_1_id = pt.id_participante_torneo)
                    OR (et.competidor_2_type = 'PARTICIPANTE' AND et.competidor_2_id = pt.id_participante_torneo)
                   )
            WHERE  pt.participante_type = 'SOCIO'
              AND  pt.participante_id   = ?
              AND  CAST(et.fecha_hora_inicio AS DATE) = ?
              AND  et.fecha_hora_inicio IS NOT NULL
              AND  et.fecha_hora_fin    IS NOT NULL
              AND  et.estatus_encuentro NOT IN ('CANCELADO', 'FINALIZADO', 'BYE')

            ORDER BY inicio
        ", [
            $id_espacio, $fecha, $ahora, $id_socio,  // Bloque 1a reservas espacio: 4 params
            $fecha, $id_espacio,                       // Bloque 1b sesiones espacio: 2 params
            $id_espacio, $fecha,                       // Bloque 1c torneos espacio: 2 params
            $id_socio, $fecha, $id_espacio,            // Bloque 2a mis reservas otros espacios: 3 params
            $id_socio, $fecha,                         // Bloque 2b mis clases: 2 params
            $id_socio, $fecha,                         // Bloque 2c mis torneos: 2 params
        ]);

        return response()->json(['success' => true, 'data' => $bloques]);
    }
}
