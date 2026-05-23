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
use Illuminate\Validation\ValidationException;

class PlantillaProgramacionController extends Controller
{
    // =========================================================================
    // DRAFT — arquitectura UPSERT (un único registro por gerente)
    //
    // La tabla drafts_programacion mantiene como máximo UNA fila por id_gerente.
    // No se hacen INSERT + DELETE en cada sesión: el registro vive mientras el
    // gerente no publique. Publicar vacía el payload (no borra la fila).
    //
    // Endpoints:
    //   GET  /drafts/activo   → devuelve o crea (upsert) el draft del gerente
    //   PUT  /drafts/activo   → actualiza el payload (upsert)
    //   POST /drafts/publicar → INSERT masivo en actividades_plantilla + vacía payload
    // =========================================================================

    // GET /api/v1/programacion/drafts/activo?id_plantilla={id}
    // Devuelve el draft de (gerente + plantilla) o lo crea vacío si no existe.
    // El frontend siempre obtiene un registro válido — nunca un 404.
    public function showDraftActivo(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $idGerente   = Auth::user()->user_id;
        $idPlantilla = $request->query('id_plantilla');

        $draft = DraftProgramacion::firstOrCreate(
            ['id_gerente' => $idGerente, 'id_plantilla' => $idPlantilla],
            ['payload'    => (object)['actividades' => []]]
        );

        return response()->json(['data' => $draft], 200);
    }

    // PUT /api/v1/programacion/drafts/activo
    // Upsert del payload por (id_gerente, id_plantilla). Una sola query por par.
    public function updateDraftActivo(UpdateDraftRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $idGerente   = Auth::user()->user_id;
        $validated   = $request->validated();
        $idPlantilla = $validated['id_plantilla'] ?? null;

        $draft = DraftProgramacion::updateOrCreate(
            ['id_gerente'   => $idGerente, 'id_plantilla' => $idPlantilla],
            ['payload'      => $validated['payload']]
        );

        return response()->json(['data' => $draft], 200);
    }

    // POST /api/v1/programacion/drafts/consolidar
    // Mueve las sesiones del draft a la plantilla activa (ya existente) sin publicar.
    // Diferencia clave con publicar(): NO crea una nueva plantilla — inserta en la que
    // ya está ACTIVA. Ideal para el flujo "Guardar progreso" del wizard.
    //
    // Pasos:
    //   1. Lee el draft por (id_gerente, id_plantilla)
    //   2. Valida campos requeridos + conflictos de horario
    //   3. INSERT masivo en actividades_plantilla usando el id_plantilla del draft
    //   4. Vacía el payload del draft (no borra la fila)
    //   5. Devuelve { actividades_creadas, total_actividades } para que el
    //      frontend actualice el contador sin necesidad de un refetch completo
    public function consolidar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $idGerente   = Auth::user()->user_id;
        $idPlantilla = $request->input('id_plantilla');

        if (!$idPlantilla) {
            return response()->json(['message' => 'El parámetro id_plantilla es obligatorio.'], 422);
        }

        $draft = DraftProgramacion::where('id_gerente', $idGerente)
            ->where('id_plantilla', $idPlantilla)
            ->firstOrFail();

        $actividades = $draft->payload['actividades'] ?? [];

        if (empty($actividades)) {
            return response()->json(['message' => 'El draft no contiene sesiones para consolidar.'], 422);
        }

        $plantilla = PlantillaProgramacion::withoutGlobalScopes()->findOrFail($idPlantilla);

        $this->validarCamposRequeridos($actividades);
        $this->validarConflictosYCongruencia($actividades);

        $resultado = DB::transaction(function () use ($draft, $actividades, $plantilla) {
            $rows = array_map(fn($a) => [
                'id_plantilla'  => $plantilla->id_plantilla,
                'id_disciplina' => $a['id_disciplina'],
                'id_espacio'    => $a['id_espacio'],
                'id_instructor' => $a['id_instructor'],
                'dia_semana'    => $a['dia_semana'],
                'hora_inicio'   => $a['hora_inicio'],
                'hora_fin'      => $a['hora_fin'],
                'cupo_maximo'   => $a['cupo_maximo'],
                'requiere_inscripcion' => $a['requiere_inscripcion'] ?? false,
                'estatus'       => 'ACTIVO',
            ], $actividades);

            ActividadPlantilla::insert($rows);
            $draft->update(['payload' => ['actividades' => []]]);

            $total = ActividadPlantilla::where('id_plantilla', $plantilla->id_plantilla)
                ->where('estatus', 'ACTIVO')
                ->count();

            return [
                'actividades_creadas' => count($rows),
                'total_actividades'   => $total,
            ];
        });

        return response()->json(['data' => $resultado], 200);
    }

    // POST /api/v1/programacion/drafts/publicar
    // Lee el draft activo del gerente, valida, hace INSERT masivo y vacía el payload.
    // No borra el registro — lo deja listo para la próxima programación.
    public function publicar(PublicarDraftRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $idGerente = Auth::user()->user_id;

        $draft       = DraftProgramacion::where('id_gerente', $idGerente)->firstOrFail();
        $payload     = $draft->payload ?? [];
        $actividades = $payload['actividades'] ?? [];

        if (empty($actividades)) {
            return response()->json([
                'message' => 'El draft no contiene actividades para publicar.',
            ], 422);
        }

        $this->validarCamposRequeridos($actividades);
        $this->validarConflictosYCongruencia($actividades);

        $resultado = DB::transaction(function () use ($draft, $payload, $actividades) {
            $plantilla = PlantillaProgramacion::create([
                'nombre_plantilla'  => $payload['nombre_plantilla'] ?? 'Plantilla sin nombre',
                'fecha_inicio'      => $payload['fecha_inicio'] ?? null,
                'fecha_fin'         => $payload['fecha_fin'] ?? null,
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

            // Vaciar el payload — el registro persiste para la próxima programación
            $draft->update(['payload' => ['actividades' => []]]);

            return [
                'id_plantilla'        => $plantilla->id_plantilla,
                'actividades_creadas' => count($rows),
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
            ->orderBy('id_plantilla')
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

    // -------------------------------------------------------------------------
    // Helpers de validación pre-transacción
    // -------------------------------------------------------------------------

    /**
     * Valida que cada actividad del payload tenga los campos mínimos requeridos.
     * Lanza ValidationException con todos los índices fallidos de una sola vez.
     *
     * @param array<int, array<string, mixed>> $actividades
     * @throws ValidationException
     */
    private function validarCamposRequeridos(array $actividades): void
    {
        $camposRequeridos = [
            'id_disciplina', 'id_espacio', 'id_instructor',
            'dia_semana', 'hora_inicio', 'hora_fin', 'cupo_maximo',
        ];

        $errors = [];
        foreach ($actividades as $idx => $actividad) {
            foreach ($camposRequeridos as $campo) {
                if (!isset($actividad[$campo])) {
                    $errors["actividades.{$idx}.{$campo}"][] = "El campo '{$campo}' es obligatorio.";
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Algoritmo O(n log n) para detectar conflictos de horario y congruencia espacio-disciplina.
     *
     * Estrategia:
     *  1. Conflictos de espacio: agrupa por (id_espacio + dia_semana), ordena por hora_inicio.
     *     Un conflicto ocurre cuando hora_inicio[i] < hora_fin[i-1] (solapamiento real).
     *     Clases adyacentes (hora_inicio == hora_fin anterior) son válidas.
     *
     *  2. Conflictos de instructor: mismo algoritmo pero agrupando por (id_instructor + dia_semana).
     *     Un instructor no puede estar en dos lugares al mismo tiempo; clases adyacentes son válidas.
     *
     *  3. Congruencia espacio-disciplina: una sola query con WHERE IN carga todos los pares
     *     válidos de la tabla espacio_disciplina y valida cada actividad contra ese set.
     *
     * Si hay errores, lanza ValidationException (HTTP 422) con índices exactos del payload
     * para que el frontend pueda resaltar los campos en conflicto en la UI.
     *
     * @param array<int, array<string, mixed>> $actividades
     * @throws ValidationException
     */
    private function validarConflictosYCongruencia(array $actividades): void
    {
        $errors = [];

        // --- 1 & 2: Detección de solapamientos (espacio y instructor) ---
        // Cada dimensión se resuelve con el mismo algoritmo de sweep-line tras ordenamiento.
        $dimensiones = [
            'espacio' => [
                'grupo_key'   => fn($a) => $a['id_espacio'] . '|' . $a['dia_semana'],
                'error_campo' => 'id_espacio',
                'etiqueta'    => 'espacio',
            ],
            'instructor' => [
                'grupo_key'   => fn($a) => $a['id_instructor'] . '|' . $a['dia_semana'],
                'error_campo' => 'id_instructor',
                'etiqueta'    => 'instructor',
            ],
        ];

        foreach ($dimensiones as $dim) {
            // Paso 1 — agrupar conservando el índice original del payload
            $grupos = [];
            foreach ($actividades as $idx => $actividad) {
                $key            = ($dim['grupo_key'])($actividad);
                $grupos[$key][] = ['idx' => $idx, 'actividad' => $actividad];
            }

            // Paso 2 — ordenar cada grupo por hora_inicio (O(k log k) por grupo → O(n log n) total)
            foreach ($grupos as &$grupo) {
                usort($grupo, fn($a, $b) => strcmp(
                    $a['actividad']['hora_inicio'],
                    $b['actividad']['hora_inicio']
                ));
            }
            unset($grupo);

            // Paso 3 — sweep: un solo pase lineal por grupo detecta todos los solapamientos
            foreach ($grupos as $grupo) {
                for ($i = 1; $i < \count($grupo); $i++) {
                    $prev    = $grupo[$i - 1];
                    $current = $grupo[$i];

                    // Solapamiento real: la clase actual empieza ANTES de que termine la anterior.
                    // Clases adyacentes (hora_inicio == hora_fin anterior) son válidas y se excluyen.
                    if ($current['actividad']['hora_inicio'] < $prev['actividad']['hora_fin']) {
                        $etiqueta = $dim['etiqueta'];
                        $campo    = $dim['error_campo'];
                        $idValor  = $current['actividad'][$campo];

                        $errors["actividades.{$current['idx']}.hora_inicio"][] =
                            "Conflicto de {$etiqueta} (id={$idValor}, día={$current['actividad']['dia_semana']}): "
                            . "se solapa con la actividad en el índice {$prev['idx']} "
                            . "({$prev['actividad']['hora_inicio']}-{$prev['actividad']['hora_fin']}).";

                        // Marcamos también el índice anterior para que el frontend resalte ambos extremos
                        $errors["actividades.{$prev['idx']}.hora_fin"][] =
                            "Conflicto de {$etiqueta} (id={$idValor}, día={$prev['actividad']['dia_semana']}): "
                            . "se solapa con la actividad en el índice {$current['idx']} "
                            . "({$current['actividad']['hora_inicio']}-{$current['actividad']['hora_fin']}).";
                    }
                }
            }
        }

        // --- 3: Congruencia espacio-disciplina (una sola query con WHERE IN) ---
        // Construye el set de pares únicos requeridos por el payload
        $paresRequeridos = [];
        foreach ($actividades as $actividad) {
            $paresRequeridos[$actividad['id_espacio']][] = $actividad['id_disciplina'];
        }

        // Trae de la BD todos los pares válidos para los espacios involucrados.
        // Construimos un set asociativo "id_espacio|id_disciplina" => true para lookups O(1).
        $espacioIds   = array_keys($paresRequeridos);
        $paresValidos = DB::table('espacio_disciplina')
            ->whereIn('id_espacio', $espacioIds)
            ->select('id_espacio', 'id_disciplina')
            ->get()
            ->mapWithKeys(fn($row) => [$row->id_espacio . '|' . $row->id_disciplina => true])
            ->all();

        foreach ($actividades as $idx => $actividad) {
            $clave = $actividad['id_espacio'] . '|' . $actividad['id_disciplina'];
            if (!isset($paresValidos[$clave])) {
                $errors["actividades.{$idx}.id_disciplina"][] =
                    "El espacio id={$actividad['id_espacio']} no soporta la disciplina id={$actividad['id_disciplina']} "
                    . "(sin relación en espacio_disciplina).";
                $errors["actividades.{$idx}.id_espacio"][] =
                    "El espacio id={$actividad['id_espacio']} no soporta la disciplina id={$actividad['id_disciplina']} "
                    . "(sin relación en espacio_disciplina).";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    // PATCH /api/v1/programacion/plantillas/{id}
    public function updatePlantilla(Request $request, int $id): JsonResponse
    {
        $plantilla = PlantillaProgramacion::withoutGlobalScopes()->findOrFail($id);

        $data = $request->validate([
            'nombre_plantilla'  => 'sometimes|string|max:255',
            'fecha_inicio'      => 'sometimes|nullable|date',
            'fecha_fin'         => 'sometimes|nullable|date|after_or_equal:fecha_inicio',
            'estatus_plantilla' => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        // Al pasar a INACTIVO, limpiar fechas de SOLO esta plantilla
        if (isset($data['estatus_plantilla']) && $data['estatus_plantilla'] === 'INACTIVO') {
            $data['fecha_inicio'] = null;
            $data['fecha_fin']    = null;
        }

        $plantilla->update($data);

        return response()->json(['data' => $plantilla->fresh()], 200);
    }

    // POST /api/v1/programacion/plantillas
    public function storePlantilla(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_plantilla' => 'required|string|max:255',
            'fecha_inicio'     => 'nullable|date',
            'fecha_fin'        => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $plantilla = PlantillaProgramacion::create([
            'nombre_plantilla'  => $data['nombre_plantilla'],
            'fecha_inicio'      => $data['fecha_inicio'],
            'fecha_fin'         => $data['fecha_fin'],
            'estatus_plantilla' => 'INACTIVO',
        ]);

        return response()->json(['data' => $plantilla], 201);
    }

    // DELETE /api/v1/programacion/plantillas/{id}
    public function destroyPlantilla(int $id): JsonResponse
    {
        $plantilla = PlantillaProgramacion::withoutGlobalScopes()->findOrFail($id);

        // Regla 1: Bloqueo absoluto si está ACTIVO
        if ($plantilla->estatus_plantilla === 'ACTIVO') {
            return response()->json([
                'message' => 'No se puede eliminar una programación que se encuentra actualmente ACTIVA.',
            ], 422);
        }

        // Regla 2: Verificación de producción mediante EXISTS indexado
        $tieneHistorial = DB::table('sesiones_activas')
            ->whereExists(function ($query) use ($id) {
                $query->select(DB::raw(1))
                    ->from('actividades_plantilla')
                    ->whereColumn('actividades_plantilla.id_actividad_plantilla', 'sesiones_activas.id_actividad_plantilla')
                    ->where('actividades_plantilla.id_plantilla', $id);
            })
            ->exists();

        if ($tieneHistorial) {
            // Escenario B: Soft Delete — preserva datos históricos para BI
            DB::transaction(function () use ($plantilla) {
                ActividadPlantilla::where('id_plantilla', $plantilla->id_plantilla)->delete();
                $plantilla->delete();
            });

            return response()->json([
                'message'  => 'La programación histórica ha sido archivada de forma segura sin afectar los reportes estadísticos.',
                'scenario' => 'soft',
            ], 200);
        }

        // Escenario A: Hard Delete — borrador limpio que nunca fue a producción
        DB::transaction(function () use ($plantilla) {
            ActividadPlantilla::where('id_plantilla', $plantilla->id_plantilla)->forceDelete();
            $plantilla->forceDelete();
        });

        return response()->json([
            'message'  => 'La plantilla borrador y sus bloques temporales han sido eliminados físicamente del sistema.',
            'scenario' => 'hard',
        ], 200);
    }

    // GET /api/v1/programacion/plantillas/{id}
    public function show(int $id): JsonResponse
    {
        $plantilla = PlantillaProgramacion::withoutGlobalScopes()->with([
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

    // PATCH /api/v1/programacion/actividades/{id}
    // Actualiza campos editables de una actividad ya confirmada en actividades_plantilla.
    public function updateActividad(Request $request, int $id): JsonResponse
    {
        $actividad = ActividadPlantilla::where('estatus', 'ACTIVO')->findOrFail($id);

        $validated = $request->validate([
            'id_disciplina'        => 'sometimes|integer|exists:disciplinas,id_disciplina',
            'id_espacio'           => 'sometimes|integer|exists:espacios_fisicos,id_espacio',
            'id_instructor'        => 'sometimes|integer|exists:instructores,id_instructor',
            'dia_semana'           => 'sometimes|string|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'hora_inicio'          => 'sometimes|date_format:H:i',
            'hora_fin'             => 'sometimes|date_format:H:i|after:hora_inicio',
            'cupo_maximo'          => 'sometimes|integer|min:1|max:40',
            'requiere_inscripcion' => 'sometimes|boolean',
        ]);

        $actividad->update($validated);

        return response()->json(['data' => ['id_actividad_plantilla' => $actividad->id_actividad_plantilla]], 200);
    }

    // DELETE /api/v1/programacion/actividades/{id}
    // Soft-delete de una actividad confirmada (la saca del calendario pero no la borra físicamente).
    public function destroyActividad(int $id): JsonResponse
    {
        $actividad = ActividadPlantilla::where('estatus', 'ACTIVO')->findOrFail($id);
        $actividad->delete();

        return response()->json(['message' => 'Actividad eliminada correctamente.'], 200);
    }
}
