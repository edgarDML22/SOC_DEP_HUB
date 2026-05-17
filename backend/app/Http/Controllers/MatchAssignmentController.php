<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\Torneo;
use Illuminate\Http\Request;

class MatchAssignmentController extends Controller
{
    public function assign(Request $request, int $id_encuentro)
    {

        $request->validate([
            'id_arbitro' => 'required|integer',
            'id_espacio' => 'required|integer',
            'fecha_hora_inicio' => 'required|date',
            'fecha_hora_fin' => 'required|date|after:fecha_hora_inicio'
        ]);


        $encuentro = EncuentrosTorneo::find($id_encuentro);
        if (!$encuentro) {
            return response()->json([
                'message' => 'Encuentro no encontrado'
            ], 404);
        }

        $torneo = Torneo::select(
            'id_torneo',
            'pool_arbitros'
        )
            ->where(
                'id_torneo',
                $encuentro->id_torneo
            )
            ->first();
        if (!$torneo) {

            return response()->json([
                'message' => 'Torneo no encontrado'
            ], 404);
        }

        if (
            !in_array(
                $request->id_arbitro,
                $torneo->pool_arbitros ?? []
            )
        ) {
            return response()->json([
                'message' => 'El árbitro no pertenece al pool del torneo'
            ], 422);
        }

        $horaInicio = date(
            'H:i',
            strtotime($request->fecha_hora_inicio)
        );

        $horaFin = date(
            'H:i',
            strtotime($request->fecha_hora_fin)
        );



        $conflictoActividad = ActividadPlantilla::query()
            ->where(
                'id_instructor',
                $request->id_arbitro
            )
            ->whereTime(
                'hora_inicio',
                '<',
                $horaFin
            )
            ->whereTime(
                'hora_fin',
                '>',
                $horaInicio
            )
            ->exists();

        if ($conflictoActividad) {

            return response()->json([
                'message' => 'El árbitro tiene actividades asignadas en ese horario'
            ], 409);
        }

        $conflictoEncuentro = EncuentrosTorneo::query()
            ->where(
                'id_arbitro_asignado',
                $request->id_arbitro
            )
            ->where(
                'id_encuentro',
                '!=',
                $id_encuentro
            )
            ->where(
                'fecha_hora_inicio',
                '<',
                $request->fecha_hora_fin
            )
            ->where(
                'fecha_hora_fin',
                '>',
                $request->fecha_hora_inicio
            )
            ->whereNotIn('estatus_encuentro', [
                'BYE',
                'FINALIZADO',

            ])
            ->exists();

        if ($conflictoEncuentro) {

            return response()->json([
                'message' => 'El árbitro ya tiene un encuentro asignado en ese horario'
            ], 409);
        }

        $encuentro->update([
            'id_arbitro_asignado' => $request->id_arbitro,
            'id_espacio' => $request->id_espacio,
            'fecha_hora_inicio' => $request->fecha_hora_inicio,
            'fecha_hora_fin' => $request->fecha_hora_fin,
            'estatus_encuentro' => 'PENDIENTE'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' => 'Encuentro asignado correctamente',

            'encuentro' => $encuentro->fresh()
        ]);
    }
}