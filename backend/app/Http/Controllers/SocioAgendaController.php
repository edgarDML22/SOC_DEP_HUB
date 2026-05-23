<?php

namespace App\Http\Controllers;

use App\Services\AgendaUnificadaService;
use App\Models\EncuentrosTorneo;
use App\Models\MiembrosFamiliares;
use App\Models\ParticipantesTorneo;
use App\Models\SocioTitular;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SocioAgendaController extends Controller
{
    public function __construct(private AgendaUnificadaService $agendaService) {}

    // US-28 / SDH-348,349,350: Agenda unificada del socio (reservas + clases)
    public function miAgenda(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
        ]);

        $idSocio = $request->user()->user_id;

        $tz    = 'America/Mexico_City';
        $desde = $request->desde ?? Carbon::now($tz)->toDateString();
        $hasta = $request->hasta ?? Carbon::now($tz)->addDays(7)->toDateString();

        $resultado = $this->agendaService->obtenerAgenda($idSocio, $desde, $hasta);

        return response()->json(['data' => $resultado], 200);
    }

    /**
     * Encuentros de torneo del socio y su familia.
     * Filtrado en backend: excluye estatus_encuentro CANCELADO/BYE y torneos CANCELADO.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['socio_titular', 'miembro_familiar'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Rol inválido.',
            ], 403);
        }

        $socioId = $user->rol === 'miembro_familiar'
            ? MiembrosFamiliares::where('id_miembro', $user->user_id)->value('socio_id')
            : $user->user_id;

        if (!$socioId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $familiarIds = MiembrosFamiliares::where('socio_id', $socioId)->pluck('id_miembro');

        // participantes_torneo: tipo_entidad (enum_tipo_participante_torneo) + referencia_id
        $participanteIds = ParticipantesTorneo::query()
            ->where('estatus_participacion', 'ACTIVO')
            ->where(function ($q) use ($socioId, $familiarIds) {
                $q->where(function ($sub) use ($socioId) {
                    $sub->where('tipo_entidad', 'SOCIO_TITULAR')
                        ->where('referencia_id', $socioId);
                });
                if ($familiarIds->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($familiarIds) {
                        $sub->where('tipo_entidad', 'MIEMBRO_FAMILIAR')
                            ->whereIn('referencia_id', $familiarIds);
                    });
                }
                // Compatibilidad con registros morph (alias SOCIO/FAMILIAR o FQCN)
                $q->orWhere(function ($sub) use ($socioId) {
                    $sub->whereIn('participante_type', ['SOCIO', SocioTitular::class])
                        ->where('participante_id', $socioId);
                });
                if ($familiarIds->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($familiarIds) {
                        $sub->whereIn('participante_type', ['FAMILIAR', MiembrosFamiliares::class])
                            ->whereIn('participante_id', $familiarIds);
                    });
                }
            })
            ->pluck('id_participante_torneo');

        if ($participanteIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // encuentros_torneo: competidor_* apunta a id_participante_torneo con alias PARTICIPANTE
        $competidorType = 'PARTICIPANTE';
        $hoy = Carbon::today('America/Mexico_City');

        $encuentros = EncuentrosTorneo::with(['torneo', 'espacioFisico'])
            ->where(function ($q) use ($participanteIds, $competidorType) {
                $q->where(function ($sub) use ($participanteIds, $competidorType) {
                    $sub->where('competidor_1_type', $competidorType)
                        ->whereIn('competidor_1_id', $participanteIds);
                })->orWhere(function ($sub) use ($participanteIds, $competidorType) {
                    $sub->where('competidor_2_type', $competidorType)
                        ->whereIn('competidor_2_id', $participanteIds);
                });
            })
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'BYE'])
            ->whereHas('torneo', fn ($q) => $q->where('estatus_torneo', '!=', 'CANCELADO'))
            ->whereNotNull('fecha_hora_inicio')
            ->whereDate('fecha_hora_inicio', '>=', $hoy)
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
