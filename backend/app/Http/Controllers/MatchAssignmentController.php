<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\Torneo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchAssignmentController extends Controller
{
    private function getFaseIndex($fase): int
    {
        return match (strtoupper($fase ?? '')) {
            '16VOS' => 1,
            '8VOS' => 2,
            'CUARTOS' => 3,
            'SEMIFINALES' => 4,
            'FINAL' => 5,
            default => 99,
        };
    }

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
            'id_disciplina',
            'fecha_fin'
        )
            ->where('id_torneo', $encuentro->id_torneo)
            ->first();

        if (!$torneo) {
            return response()->json([
                'message' => 'Torneo no encontrado'
            ], 404);
        }

        // VALIDACIÓN DE DISCIPLINA (Task 4)
        $esDeDisciplina = DB::table('instructor_disciplina')
            ->where('id_instructor', $request->id_arbitro)
            ->where('id_disciplina', $torneo->id_disciplina)
            ->exists();

        if (!$esDeDisciplina) {
            return response()->json([
                'message' => 'El árbitro no pertenece a la disciplina del torneo'
            ], 422);
        }

        // VALIDACIÓN DE ESPACIO FÍSICO POR DISCIPLINA
        $espacioDeDisciplina = DB::table('espacio_disciplina')
            ->where('id_espacio', $request->id_espacio)
            ->where('id_disciplina', $torneo->id_disciplina)
            ->exists();

        if (!$espacioDeDisciplina) {
            return response()->json([
                'message' => 'El espacio físico no está habilitado para la disciplina del torneo'
            ], 422);
        }

        // VALIDACIÓN CRONOLÓGICA (Task 5)
        $faseIndex = $this->getFaseIndex($encuentro->fase_bracket);
        $horaInicioNueva = Carbon::parse($request->fecha_hora_inicio);
        $horaFinNueva = Carbon::parse($request->fecha_hora_fin);

        if ($faseIndex !== 99) {
            $otrosEncuentros = EncuentrosTorneo::where('id_torneo', $torneo->id_torneo)
                ->whereNotNull('fecha_hora_inicio')
                ->where('id_encuentro', '!=', $id_encuentro)
                ->get();

            foreach ($otrosEncuentros as $otro) {
                $otroIndex = $this->getFaseIndex($otro->fase_bracket);
                
                if ($otroIndex === 99) continue;

                if ($otroIndex < $faseIndex) {
                    if ($horaInicioNueva->lessThan(Carbon::parse($otro->fecha_hora_fin))) {
                        return response()->json([
                            'message' => 'No es posible programar este encuentro antes de que finalice una ronda previa del torneo.'
                        ], 422);
                    }
                }
                
                if ($otroIndex > $faseIndex) {
                    if ($horaFinNueva->greaterThan(Carbon::parse($otro->fecha_hora_inicio))) {
                        return response()->json([
                            'message' => 'No es posible programar este encuentro después del inicio de una ronda posterior del torneo.'
                        ], 422);
                    }
                }
            }
        }

        $horaInicio = $horaInicioNueva->format('H:i');
        $horaFin = $horaFinNueva->format('H:i');
        
        $diasMap = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIERCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SABADO'];
        $diaSemana = $diasMap[$horaInicioNueva->dayOfWeek];

        $conflictoActividad = ActividadPlantilla::query()
            ->where('id_instructor', $request->id_arbitro)
            ->where('dia_semana', $diaSemana)
            ->whereTime('hora_inicio', '<', $horaFin)
            ->whereTime('hora_fin', '>', $horaInicio)
            ->exists();

        if ($conflictoActividad) {
            return response()->json([
                'message' => 'El árbitro tiene actividades asignadas en ese horario'
            ], 409);
        }

        // VALIDACIÓN: Árbitro NO puede estar en dos encuentros a la MISMA HORA y MISMO DÍA
        // (sin importar la cancha). Esto es porque no puede estar en dos lugares a la vez.
        $horaInicioString = $horaInicioNueva->format('H:i');
        $diaInicioString = $horaInicioNueva->format('Y-m-d');

        $conflictoHoraYDia = EncuentrosTorneo::query()
            ->where('id_arbitro_asignado', $request->id_arbitro)
            ->where('id_encuentro', '!=', $id_encuentro)
            ->whereNotIn('estatus_encuentro', ['BYE', 'FINALIZADO'])
            ->whereDate('fecha_hora_inicio', $diaInicioString)
            ->whereTime('fecha_hora_inicio', $horaInicioString)
            ->exists();

        if ($conflictoHoraYDia) {
            return response()->json([
                'message' => 'El árbitro ya tiene un encuentro asignado a la misma hora en el mismo día'
            ], 409);
        }

        $conflictoEncuentro = EncuentrosTorneo::query()
            ->where('id_arbitro_asignado', $request->id_arbitro)
            ->where('id_encuentro', '!=', $id_encuentro)
            ->where('fecha_hora_inicio', '<', $request->fecha_hora_fin)
            ->where('fecha_hora_fin', '>', $request->fecha_hora_inicio)
            ->whereNotIn('estatus_encuentro', ['BYE', 'FINALIZADO'])
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

        // ACTUALIZAR FECHA FIN DE TORNEO SI ES LA FINAL (Task 6)
        if (strtoupper($encuentro->fase_bracket ?? '') === 'FINAL') {
            $nuevaFechaFin = $horaFinNueva->toDateString();
            // Convertimos la original a string para comparar, asumiendo formato Y-m-d
            $originalFechaFin = $torneo->fecha_fin ? Carbon::parse($torneo->fecha_fin)->toDateString() : null;
            
            if ($originalFechaFin !== $nuevaFechaFin) {
                $torneo->update(['fecha_fin' => $nuevaFechaFin]);
            }
        }

        return response()->json([
            'message' => 'Encuentro asignado correctamente',
            'encuentro' => $encuentro->fresh()
        ]);
    }
}