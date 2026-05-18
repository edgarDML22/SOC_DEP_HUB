<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicarDraftRequest;
use App\Http\Requests\UpdateDraftRequest;
use App\Models\ActividadPlantilla;
use App\Models\DraftProgramacion;
use App\Models\PlantillaProgramacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlantillaProgramacionController extends Controller
{
    // POST /api/v1/programacion/drafts
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $idGerente = $user->user_id;

        $existing = DraftProgramacion::delGerente($idGerente)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Ya existe un draft activo. Finalízalo antes de crear uno nuevo.',
                'data'    => $existing,
            ], 409);
        }

        $draft = DraftProgramacion::create([
            'id_gerente' => $idGerente,
            'payload'    => [],
        ]);

        return response()->json(['data' => $draft], 201);
    }

    // PATCH /api/v1/programacion/drafts/{id}
    public function update(UpdateDraftRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $draft = DraftProgramacion::findOrFail($id);

        if ($draft->id_gerente !== $user->user_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $draft->update(['payload' => $request->validated()['payload']]);

        return response()->json(['data' => $draft->fresh()], 200);
    }

    // GET /api/v1/programacion/drafts/{id}
    public function showDraft(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $draft = DraftProgramacion::findOrFail($id);

        if ($draft->id_gerente !== $user->user_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json(['data' => $draft], 200);
    }

    // DELETE /api/v1/programacion/drafts/{id}
    public function destroyDraft(int $id): JsonResponse
    {
        $draft = DraftProgramacion::findOrFail($id);

        if ($draft->id_gerente !== Auth::user()->user_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $draft->delete();

        return response()->json(['message' => 'Draft eliminado.'], 200);
    }

    // POST /api/v1/programacion/drafts/{id}/publicar
    public function publicar(PublicarDraftRequest $request, int $id): JsonResponse
    {
        $draft = DraftProgramacion::findOrFail($id);

        if ($draft->id_gerente !== Auth::user()->user_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $payload     = $draft->payload;
        $actividades = $payload['actividades'] ?? [];

        if (empty($actividades)) {
            return response()->json([
                'message' => 'El draft no contiene actividades para publicar.',
            ], 422);
        }

        $camposRequeridos = ['id_disciplina', 'id_espacio', 'id_instructor', 'dia_semana', 'hora_inicio', 'hora_fin', 'cupo_maximo'];
        foreach ($actividades as $idx => $actividad) {
            foreach ($camposRequeridos as $campo) {
                if (!isset($actividad[$campo])) {
                    return response()->json([
                        'message' => "La actividad en el índice {$idx} no tiene el campo requerido: {$campo}.",
                    ], 422);
                }
            }
        }

        // Detectar solapamientos: mismo espacio + mismo día con rangos que se intersectan
        $conflictos = [];
        $total = \count($actividades);
        for ($i = 0; $i < $total; $i++) {
            for ($j = $i + 1; $j < $total; $j++) {
                $a = $actividades[$i];
                $b = $actividades[$j];

                if ($a['id_espacio'] !== $b['id_espacio'] || $a['dia_semana'] !== $b['dia_semana']) {
                    continue;
                }

                if ($a['hora_inicio'] < $b['hora_fin'] && $b['hora_inicio'] < $a['hora_fin']) {
                    $conflictos[] = [
                        'espacio'                       => $a['id_espacio'],
                        'dia'                           => $a['dia_semana'],
                        'hora_inicio'                   => $a['hora_inicio'],
                        'hora_fin'                      => $a['hora_fin'],
                        'actividad_index'               => $i,
                        'conflicto_con_actividad_index' => $j,
                    ];
                }
            }
        }

        if (!empty($conflictos)) {
            return response()->json([
                'message' => 'Se detectaron conflictos de horario.',
                'errors'  => $conflictos,
            ], 422);
        }

        $resultado = DB::transaction(function () use ($draft, $payload, $actividades) {
            $plantilla = PlantillaProgramacion::create([
                'nombre_plantilla'  => $payload['nombre_plantilla'] ?? 'Plantilla sin nombre',
                'fecha_inicio'      => $payload['fecha_inicio'],
                'fecha_fin'         => $payload['fecha_fin'],
                'estatus_plantilla' => 'ACTIVO',
            ]);

            $rows = array_map(fn($a) => [
                'id_plantilla'  => $plantilla->id_plantilla,
                'id_disciplina' => $a['id_disciplina'],
                'id_espacio'    => $a['id_espacio'],
                'id_instructor' => $a['id_instructor'],
                'dia_semana'    => $a['dia_semana'],
                'hora_inicio'   => $a['hora_inicio'],
                'hora_fin'      => $a['hora_fin'],
                'cupo_maximo'   => $a['cupo_maximo'],
                'estatus'       => 'ACTIVO',
            ], $actividades);

            ActividadPlantilla::insert($rows);
            $draft->delete();

            return [
                'id_plantilla'       => $plantilla->id_plantilla,
                'actividades_creadas' => \count($rows),
            ];
        });

        Artisan::call('programacion:generar-sesiones', [
            '--desde'     => now()->toDateString(),
            '--hasta'     => now()->addDays(14)->toDateString(),
            '--plantilla' => $resultado['id_plantilla'],
        ]);

        return response()->json([
            'message' => 'Programación publicada.',
            'data'    => $resultado,
        ], 201);
    }

    // GET /api/v1/programacion/plantillas
    public function index(): JsonResponse
    {
        $plantillas = PlantillaProgramacion::withoutGlobalScopes()
            ->withCount('actividades')
            ->orderByDesc('id_plantilla')
            ->get()
            ->map(fn($p) => [
                'id_plantilla'      => $p->id_plantilla,
                'nombre_plantilla'  => $p->nombre_plantilla,
                'fecha_inicio'      => $p->fecha_inicio,
                'fecha_fin'         => $p->fecha_fin,
                'estatus_plantilla' => $p->estatus_plantilla,
                'total_actividades' => $p->actividades_count,
            ]);

        return response()->json(['data' => $plantillas], 200);
    }

    // GET /api/v1/programacion/plantillas/{id}
    public function show(int $id): JsonResponse
    {
        $plantilla = PlantillaProgramacion::with([
            'actividades' => function ($query) {
                $query->where('estatus', 'ACTIVO')
                    ->with([
                        'espacioFisico:id_espacio,nombre_espacio',
                        'disciplina:id_disciplina,nombre_disciplina',
                        'instructor:id_instructor,nombre_completo',
                    ]);
            },
        ])->findOrFail($id);

        $actividades = $plantilla->actividades->map(fn($act) => [
            'id_actividad_plantilla' => $act->id_actividad_plantilla,
            'dia_semana'             => $act->dia_semana,
            'hora_inicio'            => substr($act->hora_inicio, 0, 5),
            'hora_fin'               => substr($act->hora_fin, 0, 5),
            'cupo_maximo'            => $act->cupo_maximo,
            'requiere_inscripcion'   => (bool) $act->requiere_inscripcion,
            'estatus'                => $act->estatus,
            'espacio'                => $act->espacioFisico ? [
                'id'     => $act->espacioFisico->id_espacio,
                'nombre' => $act->espacioFisico->nombre_espacio,
            ] : null,
            'disciplina'             => $act->disciplina ? [
                'id'     => $act->disciplina->id_disciplina,
                'nombre' => $act->disciplina->nombre_disciplina,
            ] : null,
            'instructor'             => $act->instructor ? [
                'id'     => $act->instructor->id_instructor,
                'nombre' => $act->instructor->nombre_completo,
            ] : null,
        ]);

        return response()->json([
            'data' => [
                'id_plantilla'      => $plantilla->id_plantilla,
                'nombre_plantilla'  => $plantilla->nombre_plantilla,
                'fecha_inicio'      => $plantilla->fecha_inicio,
                'fecha_fin'         => $plantilla->fecha_fin,
                'estatus_plantilla' => $plantilla->estatus_plantilla,
                'actividades'       => $actividades,
            ],
        ], 200);
    }
}
