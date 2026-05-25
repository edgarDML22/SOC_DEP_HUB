<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Sanciones;
use App\Models\InscripcionClase;
use App\Models\Instructor;
use App\Models\MiembrosFamiliares;
use App\Models\PasesDiarios;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SDH-308 v2 – Actividades Programadas e Inscripciones
 *
 * Extiende el controller original con:
 *   - Filtros de instructor y día de semana en indexSesiones()
 *   - listarInstructores() para poblar el select de filtros
 *   - inscribir() soporta familiar (id_miembro_familiar) e invitado (id_pase_invitado)
 *   - cancelar() con lógica de penalización (NO_SHOW) para cerradas < 2 h
 *   - misInscripciones() expone los nuevos campos
 *
 * Rutas:
 *   GET    /v1/actividades/sesiones
 *   GET    /v1/actividades/instructores
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
     * Query params opcionales:
     *   ?disciplina_id=<int>
     *   ?instructor_id=<int>
     *   ?day_of_week=<LUNES|MARTES|MIERCOLES|JUEVES|VIERNES|SABADO|DOMINGO>
     *   ?hora=<HH:MM>
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
                'actividadPlantilla:id_actividad_plantilla,id_plantilla,id_disciplina,id_espacio,id_instructor,hora_inicio,hora_fin,cupo_maximo,requiere_inscripcion,dia_semana',
                'actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'actividadPlantilla.instructor:id_instructor,nombre_completo',
                'actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
                'actividadPlantilla.plantilla:id_plantilla,nombre_plantilla,fecha_inicio,fecha_fin,estatus_plantilla',
            ])
            ->whereHas('actividadPlantilla.plantilla', function ($q) {
                $q->where('estatus_plantilla', true)
                  ->where('publicada', true)
                  ->whereColumn('plantillas_programacion.fecha_inicio', '<=', 'sesiones_activas.fecha_sesion')
                  ->whereColumn('plantillas_programacion.fecha_fin', '>=', 'sesiones_activas.fecha_sesion');
            });

        // ── Filtros opcionales ────────────────────────────────────────────────

        if ($request->filled('disciplina_id')) {
            $query->whereHas('actividadPlantilla', fn($q) =>
                $q->where('id_disciplina', $request->integer('disciplina_id'))
            );
        }

        if ($request->filled('instructor_id')) {
            $query->whereHas('actividadPlantilla', fn($q) =>
                $q->where('id_instructor', $request->integer('instructor_id'))
            );
        }

        // Filtro por día de semana: LUNES, MARTES, ... DOMINGO
        if ($request->filled('day_of_week')) {
            $dia = strtoupper($request->string('day_of_week'));
            $query->whereHas('actividadPlantilla', fn($q) =>
                $q->where('dia_semana', $dia)
            );
        }

        if ($request->filled('hora')) {
            $query->whereHas('actividadPlantilla', fn($q) =>
                $q->where('hora_inicio', 'like', $request->string('hora') . '%')
            );
        }

        $ahora = Carbon::now($tz);

        $sesiones = $query->orderBy('fecha_sesion')->get()
            ->filter(function ($s) use ($ahora) {
                $act = $s->actividadPlantilla;
                if (!$act) return false;
                
                // Combina fecha_sesion y hora_inicio
                $fechaHora = Carbon::parse("{$s->fecha_sesion} {$act->hora_inicio}", 'America/Mexico_City');
                
                // Si la fecha y hora de inicio de la sesión ya pasaron (menor que ahora), no la mostramos
                return $fechaHora->greaterThanOrEqualTo($ahora);
            })
            ->map(function ($s) {
                $act = $s->actividadPlantilla;

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
                    'dia_semana'           => $act?->dia_semana,
                    'disciplina'           => [
                        'id'     => $act?->disciplina?->id_disciplina,
                        'nombre' => $act?->disciplina?->nombre_disciplina,
                    ],
                    'instructor'           => [
                        'id'     => $act?->instructor?->id_instructor,
                        'nombre' => $act?->instructor?->nombre_completo,
                    ],
                    'espacio'              => $act?->espacioFisico?->nombre_espacio,
                ];
            })
            ->values();

        return response()->json(['data' => $sesiones], 200);
    }

    // ─── 2. Listar instructores únicos de las sesiones futuras ────────────────

    /**
     * GET /api/v1/actividades/instructores
     *
     * Devuelve la lista de instructores que tienen sesiones publicadas
     * dentro de los próximos 30 días. Sirve para poblar el select de filtros.
     */
    public function listarInstructores(): JsonResponse
    {
        $tz  = 'America/Mexico_City';
        $hoy = Carbon::now($tz)->toDateString();
        $fin = Carbon::now($tz)->addDays(30)->toDateString();

        $instructorIds = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [$hoy, $fin])
            ->whereIn('estatus_sesion', ['DISPONIBLE', 'LLENO'])
            ->join('actividades_plantilla', 'sesiones_activas.id_actividad_plantilla', '=', 'actividades_plantilla.id_actividad_plantilla')
            ->whereNull('actividades_plantilla.deleted_at')
            ->pluck('actividades_plantilla.id_instructor')
            ->unique()
            ->filter()
            ->values();

        $instructores = Instructor::whereIn('id_instructor', $instructorIds)
            ->select('id_instructor', 'nombre_completo')
            ->orderBy('nombre_completo')
            ->get()
            ->map(fn($i) => [
                'id'     => $i->id_instructor,
                'nombre' => $i->nombre_completo,
            ]);

        return response()->json(['data' => $instructores], 200);
    }

    // ─── 3. Historial de inscripciones del usuario ────────────────────────────

    /**
     * GET /api/v1/actividades/mis-inscripciones
     */
    public function misInscripciones(Request $request): JsonResponse
    {
        $user   = $request->user();
        $userId = $user->user_id;
        $rol    = $user->rol;

        if ($rol === 'socio_titular') {
            $miembrosIds = MiembrosFamiliares::where('socio_id', $userId)->pluck('id_miembro')->toArray();
            $pasesIds = PasesDiarios::whereHas('invitado', function ($q) use ($userId) {
                $q->where('socio_id', $userId);
            })->pluck('id_pase')->toArray();

            // Convertir a IDs negativos para pases de invitados
            $negativePasesIds = array_map(fn($id) => -$id, $pasesIds);

            $query = InscripcionClase::where(function ($q) use ($userId, $miembrosIds, $negativePasesIds) {
                $q->where(function ($sub) use ($userId) {
                    $sub->where('tipo_usuario', 'socio_titular')
                        ->where('id_usuario', $userId);
                })
                ->orWhere(function ($sub) use ($miembrosIds) {
                    $sub->where('tipo_usuario', 'miembro_familiar')
                        ->whereIn('id_usuario', $miembrosIds);
                })
                ->orWhere(function ($sub) use ($negativePasesIds) {
                    $sub->where('tipo_usuario', 'socio_titular')
                        ->whereIn('id_usuario', $negativePasesIds);
                });
            });
        } else {
            $query = InscripcionClase::where('id_usuario', $userId)->where('tipo_usuario', 'miembro_familiar');
        }

        $inscripciones = $query->with([
                'sesion' => fn($q) => $q->withoutGlobalScopes()
                    ->select(['id_sesion', 'id_actividad_plantilla', 'fecha_sesion', 'estatus_sesion']),
                'sesion.actividadPlantilla' => fn($q) => $q->withTrashed()
                    ->select(['id_actividad_plantilla', 'id_disciplina', 'id_instructor', 'id_espacio', 'hora_inicio', 'hora_fin', 'requiere_inscripcion']),
                'sesion.actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'sesion.actividadPlantilla.instructor:id_instructor,nombre_completo',
                'sesion.actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
                'miembroFamiliar:id_miembro,nombre_completo,parentesco',
                'paseInvitado.invitado:id_invitado,nombre_invitado',
            ])
            ->orderByDesc('fecha_transaccion')
            ->get()
            ->map(function ($i) {
                $sesion = $i->sesion;
                $act    = $sesion?->actividadPlantilla;

                return [
                    'id_inscripcion'       => $i->id_inscripcion,
                    'id_sesion'            => $sesion?->id_sesion,
                    'estatus_inscripcion'  => $i->estatus_inscripcion,
                    'fecha_transaccion'    => $i->fecha_transaccion,
                    'tipo_usuario'         => ($i->id_usuario < 0) ? 'invitado' : $i->tipo_usuario,
                    // Familiar/invitado — nullable
                    'familiar'             => $i->miembroFamiliar ? [
                        'id'         => $i->miembroFamiliar->id_miembro,
                        'nombre'     => $i->miembroFamiliar->nombre_completo,
                        'parentesco' => $i->miembroFamiliar->parentesco,
                    ] : null,
                    'invitado'             => $i->paseInvitado?->invitado ? [
                        'id_pase' => $i->paseInvitado->id_pase,
                        'nombre'  => $i->paseInvitado->invitado->nombre_invitado,
                    ] : null,
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

    // ─── 4. Estado de inscripción del usuario en una sesión ───────────────────

    /**
     * GET /api/v1/actividades/sesiones/{id_sesion}/estado-inscripcion
     */
    public function estadoInscripcion(Request $request, int $id_sesion): JsonResponse
    {
        $userId = $request->user()->user_id;

        $inscripcion = InscripcionClase::where('id_sesion', $id_sesion)
            ->where('id_usuario', $userId)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA', 'FALTA'])
            ->first();

        return response()->json([
            'data' => [
                'inscrito'            => $inscripcion !== null,
                'id_inscripcion'      => $inscripcion?->id_inscripcion,
                'estatus_inscripcion' => $inscripcion?->estatus_inscripcion,
            ],
        ], 200);
    }

    // ─── 5. Inscribir usuario a una sesión ────────────────────────────────────

    /**
     * POST /api/v1/actividades/sesiones/{id_sesion}/inscribir
     *
     * Body (todos opcionales; mutuamente excluyentes):
     *   id_miembro_familiar: int  → inscribir a un familiar del socio
     *   id_pase_invitado:    int  → inscribir a un invitado con pase activo
     *
     * Si ambos o ninguno, se inscribe al socio titular.
     *
     * Restricciones para invitados:
     *   - El pase debe estar ACTIVO
     *   - La sesión debe ser HOY (no sesiones futuras)
     *   - Pueden inscribirse en abiertas Y cerradas
     *
     * Restricciones para familiares:
     *   - El miembro debe pertenecer al socio autenticado
     */
    public function inscribir(Request $request, int $id_sesion): JsonResponse
    {
        $user   = $request->user();
        $userId = $user->user_id;
        $rol    = $user->rol;

        $idMiembroFamiliar = $request->input('id_miembro_familiar');
        $idPaseInvitado    = $request->input('id_pase_invitado');

        // ── 1. Cargar sesión ──────────────────────────────────────────────────
        $sesion = SesionActiva::withoutGlobalScopes()
            ->with(['actividadPlantilla:id_actividad_plantilla,id_disciplina,cupo_maximo,requiere_inscripcion,hora_inicio,hora_fin'])
            ->find($id_sesion);

        if (!$sesion) {
            return response()->json(['message' => 'La sesión no existe.'], 404);
        }

        if (!in_array($sesion->estatus_sesion, ['DISPONIBLE', 'LLENO'], true)) {
            return response()->json(['message' => 'Esta sesión no está disponible para inscripción.'], 422);
        }

        // ── 2. Validar socio activo ────────────────────────────────────────────
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

        // ── 3. Determinar tipo y subject de inscripción ────────────────────────
        $subjectId        = $userId;
        $tipoUsuario      = $rol === 'socio_titular' ? 'socio_titular' : 'miembro_familiar';

        if ($idMiembroFamiliar) {
            // Inscribir a un familiar del socio titular autenticado
            $socioId = $rol === 'socio_titular' ? $userId : ($miembro->socio_id ?? null);

            $familiar = MiembrosFamiliares::where('id_miembro', $idMiembroFamiliar)
                ->where('socio_id', $socioId)
                ->first();

            if (!$familiar) {
                return response()->json(['message' => 'El miembro familiar no pertenece a tu cuenta.'], 403);
            }

            $subjectId        = $familiar->id_miembro;
            $tipoUsuario      = 'miembro_familiar';

        } elseif ($idPaseInvitado) {
            // Inscribir a un invitado con pase activo

            // Solo sesiones de HOY para invitados
            $hoy = Carbon::now('America/Mexico_City')->toDateString();
            if ($sesion->fecha_sesion !== $hoy) {
                return response()->json(['message' => 'Los invitados solo pueden inscribirse en sesiones del día de hoy.'], 422);
            }

            // Validar pase activo y que pertenezca al socio
            $pase = PasesDiarios::with('invitado')
                ->where('id_pase', $idPaseInvitado)
                ->where('estatus_acceso', 'ACTIVO')
                ->first();

            if (!$pase) {
                return response()->json(['message' => 'El pase de invitado no está activo o no existe.'], 422);
            }

            // Verificar que el invitado pertenece al socio autenticado
            $socioId = $rol === 'socio_titular' ? $userId : ($miembro->socio_id ?? null);
            if (!$pase->invitado || $pase->invitado->socio_id !== $socioId) {
                return response()->json(['message' => 'Este pase de invitado no pertenece a tu cuenta.'], 403);
            }

            $subjectId   = -$pase->id_pase;
            $tipoUsuario = 'invitado';
        }

        // ── 4. Verificar inscripción duplicada ─────────────────────────────────
        $yaInscrito = InscripcionClase::where('id_sesion', $id_sesion)
            ->where('id_usuario', $subjectId)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA', 'FALTA'])
            ->exists();

        if ($yaInscrito) {
            return response()->json(['message' => 'Ya existe una inscripción activa en esta sesión para este participante.'], 422);
        }

        // ── 5. Validar colisión de horarios en su agenda ──────────────────────
        $nuevoInicio = $sesion->actividadPlantilla?->hora_inicio;
        $nuevoFin    = $sesion->actividadPlantilla?->hora_fin;
        $fechaSesion = $sesion->fecha_sesion;

        if ($nuevoInicio && $nuevoFin) {
            $colisiones = InscripcionClase::where('id_usuario', $subjectId)
                ->whereNotIn('estatus_inscripcion', ['CANCELADA', 'FALTA'])
                ->whereHas('sesion', function ($query) use ($fechaSesion) {
                    $query->withoutGlobalScopes()->where('fecha_sesion', $fechaSesion);
                })
                ->with(['sesion.actividadPlantilla:id_actividad_plantilla,hora_inicio,hora_fin'])
                ->get();

            foreach ($colisiones as $colision) {
                $actColision = $colision->sesion?->actividadPlantilla;
                if ($actColision) {
                    $existInicio = $actColision->hora_inicio;
                    $existFin    = $actColision->hora_fin;

                    if ($existInicio < $nuevoFin && $nuevoInicio < $existFin) {
                        return response()->json([
                            'message' => 'No puedes inscribirte a esta sesión porque coincide en horario con otra clase en tu agenda (' . substr($existInicio, 0, 5) . ' - ' . substr($existFin, 0, 5) . ').'
                        ], 422);
                    }
                }
            }
        }

        // ── 6. Validar cupo ────────────────────────────────────────────────────
        $actividad = $sesion->actividadPlantilla;
        if ($actividad && $actividad->requiere_inscripcion && $sesion->es_cupo_lleno) {
            return response()->json(['message' => 'La actividad ha alcanzado el límite máximo de participantes.'], 422);
        }

        // ── 7. Crear inscripción + incrementar contador ────────────────────────
        $inscripcion = DB::transaction(function () use ($sesion, $subjectId, $tipoUsuario) {
            $dbTipoUsuario = $subjectId < 0 ? 'socio_titular' : $tipoUsuario;

            $inscripcion = InscripcionClase::create([
                'id_sesion'           => $sesion->id_sesion,
                'id_usuario'          => $subjectId,
                'tipo_usuario'        => $dbTipoUsuario,
                'fecha_transaccion'   => now('America/Mexico_City')->toDateTimeString(),
                'estatus_inscripcion' => 'CONFIRMADA',
                'bloqueo_temporal'    => false,
            ]);

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
                'tipo_usuario'        => $subjectId < 0 ? 'invitado' : $inscripcion->tipo_usuario,
            ],
        ], 201);
    }

    // ─── 6. Cancelar inscripción ──────────────────────────────────────────────

    /**
     * DELETE /api/v1/actividades/inscripciones/{id_inscripcion}
     *
     * Lógica de penalización (misma arquitectura que ReservacionController::cancel()):
     *   - Solo aplica si la actividad es CERRADA (requiere_inscripcion = true)
     *   - Solo aplica a socio_titular y miembro_familiar (NO a invitados)
     *   - Si quedan < 120 min → NO_SHOW + aplicarSancionesReservas()
     *   - Si quedan >= 120 min → CANCELADA sin penalización
     *
     * Response incluye `penalizacion: bool` para que el frontend
     * pueda mostrar el modal de advertencia antes de confirmar.
     */
    public function cancelar(Request $request, int $id_inscripcion): JsonResponse
    {
        $userId = $request->user()->user_id;
        $rol    = $request->user()->rol;

        $inscripcion = InscripcionClase::find($id_inscripcion);

        if (!$inscripcion) {
            return response()->json(['message' => 'Inscripción no encontrada.'], 404);
        }

        if (in_array($inscripcion->estatus_inscripcion, ['CANCELADA', 'FALTA'])) {
            return response()->json(['message' => 'Esta inscripción ya fue cancelada.'], 422);
        }

        // Autorizar cancelación:
        $autorizado = false;

        if ($rol === 'miembro_familiar') {
            if ($inscripcion->tipo_usuario === 'miembro_familiar' && $inscripcion->id_usuario === $userId) {
                $autorizado = true;
            }
        } elseif ($rol === 'socio_titular') {
            if ($inscripcion->id_usuario < 0) {
                // Es un invitado, validamos que el pase de acceso activo pertenezca a la cuenta del socio
                $esInvitado = PasesDiarios::where('id_pase', abs($inscripcion->id_usuario))
                    ->whereHas('invitado', function ($q) use ($userId) {
                        $q->where('socio_id', $userId);
                    })
                    ->exists();
                if ($esInvitado) {
                    $autorizado = true;
                }
            } elseif ($inscripcion->tipo_usuario === 'socio_titular' && $inscripcion->id_usuario === $userId) {
                $autorizado = true;
            } elseif ($inscripcion->tipo_usuario === 'miembro_familiar') {
                $esFamiliar = MiembrosFamiliares::where('id_miembro', $inscripcion->id_usuario)
                    ->where('socio_id', $userId)
                    ->exists();
                if ($esFamiliar) {
                    $autorizado = true;
                }
            }
        }

        if (!$autorizado) {
            return response()->json(['message' => 'No tienes autorización para cancelar esta inscripción.'], 403);
        }

        // ── Cargar sesión con su actividad para calcular tiempo y tipo ─────────
        $sesion = SesionActiva::withoutGlobalScopes()
            ->with('actividadPlantilla:id_actividad_plantilla,hora_inicio,requiere_inscripcion')
            ->find($inscripcion->id_sesion);

        // ── Calcular minutos restantes ─────────────────────────────────────────
        $minutosRestantes = PHP_INT_MAX; // default seguro si no hay sesión

        if ($sesion && $sesion->actividadPlantilla) {
            $fechaHoraInicio = Carbon::parse(
                $sesion->fecha_sesion . ' ' . $sesion->actividadPlantilla->hora_inicio,
                'America/Mexico_City'
            );
            $minutosRestantes = Carbon::now('America/Mexico_City')
                ->diffInMinutes($fechaHoraInicio, false);
        }

        // ── Determinar si aplica penalización ─────────────────────────────────
        $esCerrada    = (bool) ($sesion?->actividadPlantilla?->requiere_inscripcion ?? false);
        $esInvitado   = $inscripcion->id_usuario < 0;
        $hayPenalizacion = $esCerrada && !$esInvitado && $minutosRestantes < 120;

        $nuevoEstatus = $hayPenalizacion ? 'FALTA' : 'CANCELADA';

        // ── Transacción: actualizar inscripción + decrementar contador ─────────
        DB::transaction(function () use ($inscripcion, $nuevoEstatus) {
            $inscripcion->update(['estatus_inscripcion' => $nuevoEstatus]);

            SesionActiva::withoutGlobalScopes()
                ->lockForUpdate()
                ->where('id_sesion', $inscripcion->id_sesion)
                ->where('cantidad_inscritos', '>', 0)
                ->decrement('cantidad_inscritos');
        });

        // ── Aplicar sanciones si FALTA (igual que reservas on-demand) ────────
        if ($hayPenalizacion) {
            // Determinar id del socio titular a penalizar
            $socioId = null;
            if ($inscripcion->tipo_usuario === 'socio_titular') {
                $socioId = $inscripcion->id_usuario;
            } elseif ($inscripcion->tipo_usuario === 'miembro_familiar') {
                // Obtener el socio_id del miembro familiar para pasárselo al socio titular
                $socioId = MiembrosFamiliares::where('id_miembro', $inscripcion->id_usuario)->value('socio_id');
            }

            if ($socioId) {
                // Incrementar contador del socio titular
                SocioTitular::where('id_socio', $socioId)->increment('contador_no_shows');
                // Aplicar sanción al socio titular
                Sanciones::aplicarSancionesReservas($socioId);
            }
        }

        return response()->json([
            'message'      => $hayPenalizacion
                ? 'Inscripción cancelada tardíamente. Se registró un No Show en tu cuenta.'
                : 'Inscripción cancelada correctamente.',
            'nuevo_estatus' => $nuevoEstatus,
            'penalizacion'  => $hayPenalizacion,
        ], 200);
    }
}
