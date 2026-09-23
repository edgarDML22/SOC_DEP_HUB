<?php

namespace App\Http\Controllers;

use App\Models\EncuentrosTorneo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstructorEncuentrosController extends Controller
{
    /**
     * Encuentros de torneo asignados al instructor como árbitro (hoy).
     * Filtrado en backend: excluye estatus_encuentro CANCELADO y BYE.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->rol !== 'instructor') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Rol inválido.',
            ], 403);
        }

        $instructorId = $user->user_id;
        $hoy = Carbon::today('America/Mexico_City');

        $encuentros = EncuentrosTorneo::with(['torneo', 'espacioFisico'])
            ->where('id_arbitro_asignado', $instructorId)
            ->whereDate('fecha_hora_inicio', $hoy)
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'BYE'])
            ->whereHas('torneo', fn ($q) => $q->where('estatus_torneo', '!=', 'CANCELADO'))
            ->orderBy('fecha_hora_inicio')
            ->get()
            ->map(fn ($e) => [
                'id_encuentro' => $e->id_encuentro,
                'id_torneo' => $e->id_torneo,
                'nombre_torneo' => $e->torneo?->nombre_torneo,
                'fase_bracket' => $e->fase_bracket,
                'numero_encuentro' => $e->numero_encuentro,
                'estatus_encuentro' => $e->estatus_encuentro,
                'fecha_hora_inicio' => $e->fecha_hora_inicio,
                'fecha_hora_fin' => $e->fecha_hora_fin,
                'espacio' => $e->espacioFisico?->nombre_espacio,
            ]);

        return response()->json([
            'success' => true,
            'data' => $encuentros,
        ]);
    }
}
