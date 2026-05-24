<?php

namespace App\Http\Controllers;

use App\Models\InscripcionClase;
use App\Models\MiembrosFamiliares;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SDH-308 – Actividades Programadas e Inscripciones
 *
 * Controller aislado para el módulo de inscripción de socios a sesiones.
 * NO modifica endpoints existentes ni lógica de otros módulos.
 *
 * Rutas:
 *   GET    /v1/actividades/sesiones
 *   GET    /v1/actividades/mis-inscripciones
 *   GET    /v1/actividades/sesiones/{id}/estado-inscripcion
 *   POST   /v1/actividades/sesiones/{id}/inscribir
 *   DELETE /v1/actividades/inscripciones/{id}
 */
class InscripcionClaseController extends Controller
{
    // ─── 1. Listar sesiones activas/programadas ───────────────────────────────

    /**
     * GET /api/v1/actividades/sesiones
     *
     * Devuelve sesiones publicadas y activas dentro de los próximos 30 días,
     * cuya plantilla esté vigente hoy. Soporta filtros opcionales por disciplina y hora.
     *
     * Query params:
     *   ?disciplina_id=<int>   Filtra por id_disciplina
     *   ?hora=<HH:MM>          Filtra sesiones cuya hora_inicio comience con ese valor
     *
     * Eager loading evita N+1:
     *   actividadPlantilla → disciplina, instructor, espacioFisico, plantilla
     */
    public function indexSesiones(Request $request): JsonResponse
    {
        $tz  = 'America/Mexico_City';
        $hoy = Carbon::now($tz)->toDateString();
        $fin = Carbon::now($tz)->addDays(30)->toDateString();

        $query = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [$hoy, $fin])
            ->whereIn('estatus_sesion', ['DISPONIBLE', 'LLENO'])
            ->with([
                'actividadPlantilla:id_actividad_plantilla,id_plantilla,id_disciplina,id_espacio,id_instructor,hora_inicio,hora_fin,cupo_maximo,requiere_inscripcion',
                'actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'actividadPlantilla.instructor:id_instructor,nombre_completo',
                'actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
                'actividadPlantilla.plantilla:id_plantilla,nombre_plantilla,fecha_inicio,fecha_fin,estatus_plantilla',
            ])
            // Solo sesiones cuya plantilla esté activa y su rango de fechas
            // se solape con el rango de búsqueda (hoy → fin)
            ->whereHas('actividadPlantilla.plantilla', function ($q) use ($hoy, $fin) {
                $q->where('estatus_plantilla', true)
                  ->where(function ($sub) use ($hoy, $fin) {
                      // Solapamiento: plantilla.inicio <= fin_busqueda AND plantilla.fin >= hoy
                      $sub->where('fecha_inicio', '<=', $fin)
                          ->where('fecha_fin', '>=', $hoy);
                  });
            });

        // Filtro opcional por disciplina
        if ($request->filled('disciplina_id')) {
            $query->whereHas('actividadPlantilla', function ($q) use ($request) {
                $q->where('id_disciplina', $request->integer('disciplina_id'));
            });
        }

        // Filtro opcional por hora (prefijo, e.g. "08" o "08:00")
        if ($request->filled('hora')) {
            $query->whereHas('actividadPlantilla', function ($q) use ($request) {
                $q->where('hora_inicio', 'like', $request->string('hora') . '%');
            });
        }

        $sesiones = $query->orderBy('fecha_sesion')->get()->map(function ($s) {
            $act = $s->actividadPlantilla;

            // Disponibilidad: null cuando es abierta (sin límite)
            $disponible = null;
            if ($act && $act->requiere_inscripcion && $act->cupo_maximo !== null) {
                $disponible = max(0, $act->cupo_maximo - $s->cantidad_inscritos);
            }

            return [
                'id_sesion'            => $s->id_sesion,
                'fecha_sesion'         => $s->fecha_sesion,
                'estatus_sesion'       => $s->estatus_sesion,
                'cantidad_inscritos'   => $s->cantidad_inscritos,
                'es_cupo_lleno'        => $s->es_cupo_lleno,
                'hora_inicio'          => $act ? substr($act->hora_inicio, 0, 5) : null,
                'hora_fin'             => $act ? substr($act->hora_fin, 0, 5) : null,
                'cupo_maximo'          => $act?->cupo_maximo,
                'disponible'           => $disponible,
                'requiere_inscripcion' => (bool) ($act?->requiere_inscripcion ?? false),
                'tipo_clase'           => ($act?->requiere_inscripcion) ? 'Cerrada' : 'Abierta',
                'nombre_actividad'     => $act?->disciplina?->nombre_disciplina ?? 'Actividad',
                'disciplina'           => [
                    'id'     => $act?->disciplina?->id_disciplina,
                    'nombre' => $act?->disciplina?->nombre_disciplina,
                ],
                'instructor'           => $act?->instructor?->nombre_completo,
                'espacio'              => $act?->espacioFisico?->nombre_espacio,
            ];
        });

        return response()->json(['data' => $sesiones], 200);
    }

    // ─── 2. Historial de inscripciones del usuario ────────────────────────────

    /**
     * GET /api/v1/actividades/mis-inscripciones
     *
     * Devuelve el historial de inscripciones del usuario autenticado,
     * ordenado por fecha de transacción descendente.
     * Usa withoutGlobalScopes() en sesion para ver el historial completo.
     * Usa withTrashed() en actividadPlantilla para no perder datos históricos.
     */
    public function misInscripciones(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $inscripciones = InscripcionClase::where('id_usuario', $userId)
            ->with([
                'sesion' => fn($q) => $q->withoutGlobalScopes()
                    ->select(['id_sesion', 'id_actividad_plantilla', 'fecha_sesion', 'estatus_sesion']),
                'sesion.actividadPlantilla' => fn($q) => $q->withTrashed()
                    ->select(['id_actividad_plantilla', 'id_disciplina', 'id_instructor', 'id_espacio', 'hora_inicio', 'hora_fin', 'requiere_inscripcion']),
                'sesion.actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'sesion.actividadPlantilla.instructor:id_instructor,nombre_completo',
                'sesion.actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
            ])
            ->orderByDesc('fecha_transaccion')
            ->get()
            ->map(function ($i) {
                $sesion = $i->sesion;
                $act    = $sesion?->actividadPlantilla;

                return [
                    'id_inscripcion'       => $i->id_inscripcion,
                    'estatus_inscripcion'  => $i->estatus_inscripcion,
                    'fecha_transaccion'    => $i->fecha_transaccion,
                    'tipo_usuario'         => $i->tipo_usuario,
                    'fecha_sesion'         => $sesion?->fecha_sesion,
                    'estatus_sesion'       => $sesion?->estatus_sesion,
                    'hora_inicio'          => $act ? substr($act->hora_inicio, 0, 5) : null,
                    'hora_fin'             => $act ? substr($act->hora_fin, 0, 5) : null,
                    'nombre_actividad'     => $act?->disciplina?->nombre_disciplina ?? 'Actividad',
                    'disciplina'           => $act?->disciplina?->nombre_disciplina,
                    'instructor'           => $act?->instructor?->nombre_completo,
                    'espacio'              => $act?->espacioFisico?->nombre_espacio,
                    'requiere_inscripcion' => (bool) ($act?->requiere_inscripcion ?? false),
                    'tipo_clase'           => ($act?->requiere_inscripcion) ? 'Cerrada' : 'Abierta',
                ];
            });

        return response()->json(['data' => $inscripciones], 200);
    }

    // ─── 3. Estado de inscripción del usuario en una sesión ───────────────────

    /**
     * GET /api/v1/actividades/sesiones/{id_sesion}/estado-inscripcion
     *
     * Devuelve si el usuario ya está inscrito y el estado actual.
     */
    public function estadoInscripcion(Request $request, int $id_sesion): JsonResponse
    {
        $userId = $request->user()->user_id;

        $inscripcion = InscripcionClase::where('id_sesion', $id_sesion)
            ->where('id_usuario', $userId)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA'])
            ->first();

        return response()->json([
            'data' => [
                'inscrito'            => $inscripcion !== null,
                'id_inscripcion'      => $inscripcion?->id_inscripcion,
                'estatus_inscripcion' => $inscripcion?->estatus_inscripcion,
            ],
        ], 200);
    }

    // ─── 4. Inscribir usuario a una sesión ────────────────────────────────────

    /**
     * POST /api/v1/actividades/sesiones/{id_sesion}/inscribir
     *
     * Valida y registra la inscripción del usuario autenticado.
     *
     * Validaciones:
     *   1. La sesión existe y está PUBLICADA o ACTIVA
     *   2. El usuario es socio activo (estatus_cuenta = ACTIVO)
     *   3. No existe inscripción activa previa (evita duplicados)
     *   4. Si requiere_inscripcion = true (CERRADA): valida cupo disponible
     *   5. Si requiere_inscripcion = false (ABIERTA): sin límite de cupo
     *
     * Todo dentro de una transacción DB para garantizar consistencia.
     */
    public function inscribir(Request $request, int $id_sesion): JsonResponse
    {
        $user   = $request->user();
        $userId = $user->user_id;
        $rol    = $user->rol;

        // ── 1. Cargar sesión con su actividad (eager para evitar N+1) ─────────
        $sesion = SesionActiva::withoutGlobalScopes()
            ->with(['actividadPlantilla:id_actividad_plantilla,id_disciplina,cupo_maximo,requiere_inscripcion'])
            ->find($id_sesion);

        if (!$sesion) {
            return response()->json(['message' => 'La sesión no existe.'], 404);
        }

        if (!in_array($sesion->estatus_sesion, ['DISPONIBLE', 'LLENO'], true)) {
            return response()->json(['message' => 'Esta sesión no está disponible para inscripción.'], 422);
        }

        // ── 2. Validar que el usuario sea socio activo ────────────────────────
        // Para miembro_familiar: tomar el socio titular padre
        if ($rol === 'socio_titular') {
            $socio = SocioTitular::find($userId);
        } elseif ($rol === 'miembro_familiar') {
            $miembro = MiembrosFamiliares::where('id_miembro', $userId)->first();
            $socio   = $miembro ? SocioTitular::find($miembro->socio_id) : null;
        } else {
            return response()->json(['message' => 'Rol no autorizado para inscripción.'], 403);
        }

        if (!$socio || $socio->estatus_cuenta !== 'AL_CORRIENTE') {
            return response()->json(['message' => 'Tu cuenta no está al corriente para inscribirte a actividades.'], 403);
        }

        // ── 3. Verificar inscripción duplicada ────────────────────────────────
        $yaInscrito = InscripcionClase::where('id_sesion', $id_sesion)
            ->where('id_usuario', $userId)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA'])
            ->exists();

        if ($yaInscrito) {
            return response()->json(['message' => 'Ya tienes una inscripción activa en esta sesión.'], 422);
        }

        $actividad = $sesion->actividadPlantilla;

        // ── 4. Lógica de cupo según tipo de inscripción ───────────────────────
        // requiere_inscripcion = true  → CERRADA: valida cupo máximo
        // requiere_inscripcion = false → ABIERTA: sin límite
        if ($actividad && $actividad->requiere_inscripcion) {
            if ($sesion->es_cupo_lleno) {
                return response()->json([
                    'message' => 'La actividad ha alcanzado el límite máximo de participantes.',
                ], 422);
            }
        }

        // ── 5. Crear inscripción + incrementar contador en una transacción ────
        $inscripcion = DB::transaction(function () use ($sesion, $userId, $rol) {
            $tipoUsuario = $rol === 'socio_titular' ? 'socio_titular' : 'miembro_familiar';

            $inscripcion = InscripcionClase::create([
                'id_sesion'           => $sesion->id_sesion,
                'id_usuario'          => $userId,
                'tipo_usuario'        => $tipoUsuario,
                'fecha_transaccion'   => now('America/Mexico_City')->toDateTimeString(),
                'estatus_inscripcion' => 'CONFIRMADA',
                'bloqueo_temporal'    => false,
            ]);

            // Incrementar cantidad_inscritos con lock pesimista para evitar race conditions
            SesionActiva::withoutGlobalScopes()
                ->lockForUpdate()
                ->where('id_sesion', $sesion->id_sesion)
                ->increment('cantidad_inscritos');

            return $inscripcion;
        });

        return response()->json([
            'message' => 'Inscripción realizada correctamente.',
            'data'    => [
                'id_inscripcion'      => $inscripcion->id_inscripcion,
                'estatus_inscripcion' => $inscripcion->estatus_inscripcion,
                'fecha_transaccion'   => $inscripcion->fecha_transaccion,
            ],
        ], 201);
    }

    // ─── 5. Cancelar inscripción ──────────────────────────────────────────────

    /**
     * DELETE /api/v1/actividades/inscripciones/{id_inscripcion}
     *
     * Cancela la inscripción del usuario autenticado.
     * Solo puede cancelar sus propias inscripciones.
     * Decrementa cantidad_inscritos en la sesión correspondiente.
     */
    public function cancelar(Request $request, int $id_inscripcion): JsonResponse
    {
        $userId = $request->user()->user_id;

        $inscripcion = InscripcionClase::where('id_inscripcion', $id_inscripcion)
            ->where('id_usuario', $userId)
            ->first();

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada.'], 404);
        }

        if ($inscripcion->estatus_inscripcion === 'CANCELADA') {
            return response()->json(['message' => 'Esta inscripción ya está cancelada.'], 422);
        }

        DB::transaction(function () use ($inscripcion) {
            $inscripcion->update(['estatus_inscripcion' => 'CANCELADA']);

            // Decrementar contador (mínimo 0)
            SesionActiva::withoutGlobalScopes()
                ->lockForUpdate()
                ->where('id_sesion', $inscripcion->id_sesion)
                ->where('cantidad_inscritos', '>', 0)
                ->decrement('cantidad_inscritos');
        });

        return response()->json(['message' => 'Inscripción cancelada correctamente.'], 200);
    }
}
