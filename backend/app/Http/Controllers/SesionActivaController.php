<?php

namespace App\Http\Controllers;

use App\Jobs\NotificarCancelacionSesionJob;
use App\Models\PlantillaProgramacion;
use App\Models\SesionActiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SesionActivaController extends Controller
{
    /**
     * GET /api/v1/programacion/sesiones-activas
     *
     * Lista paginada (30/página) de sesiones activas de la plantilla vigente.
     * Acepta filtros opcionales via query string:
     *   ?buscar=      texto libre contra disciplina/instructor/espacio
     *   ?estatus=     DISPONIBLE|EN_CURSO|FINALIZADA|CANCELADA
     *   ?disciplina=  nombre exacto de disciplina
     *   ?categoria=   nombre de categoría
     *   ?instructor=  nombre de instructor
     *   ?espacio=     nombre de espacio
     *   ?dia=         LUNES|MARTES|...
     *   ?hora_min=    HH:MM (hora_inicio >=)
     *   ?hora_max=    HH:MM (hora_inicio <=)
     *   ?tipo_clase=  ABIERTA|CERRADA
     *   ?page=        número de página (default 1)
     */
    public function index(Request $request): JsonResponse
    {
        app(\App\Services\PublicarProgramacionService::class)->resetearPlantillasVencidas();

        $query = SesionActiva::withoutGlobalScopes()
            ->publicadaYVigente()
            ->with([
                'actividadPlantilla:id_actividad_plantilla,id_plantilla',
                'actividadPlantilla.disciplina.categorias',
                'disciplina:id_disciplina,nombre_disciplina',
                'espacio:id_espacio,nombre_espacio',
                'instructorPrincipal:id_instructor,nombre_completo',
            ])
            ->withCount([
                'inscripcionesClase as inscripciones_count' => function ($q) {
                    $q->whereIn(DB::raw('estatus_inscripcion::text'), ['CONFIRMADA', 'ASISTIO']);
                }
            ]);

        // ── Filtros opcionales ────────────────────────────────────────────────
        if ($estatus = $request->query('estatus')) {
            $query->where('estatus_sesion', $estatus);
        }

        if ($dia = $request->query('dia')) {
            $query->where('dia_semana', $dia);
        }

        if ($horaMin = $request->query('hora_min')) {
            $query->where('hora_inicio', '>=', $horaMin);
        }

        if ($horaMax = $request->query('hora_max')) {
            $query->where('hora_inicio', '<=', $horaMax);
        }

        if ($tipoClase = $request->query('tipo_clase')) {
            $query->where('requiere_inscripcion', $tipoClase === 'CERRADA' ? true : false);
        }

        if ($disciplina = $request->query('disciplina')) {
            $query->whereHas('disciplina', fn($q) => $q->where('nombre_disciplina', $disciplina));
        }

        if ($categoria = $request->query('categoria')) {
            $query->whereHas('actividadPlantilla.disciplina.categorias', fn($q) => $q->where('nombre', $categoria));
        }

        if ($instructor = $request->query('instructor')) {
            $query->whereHas('instructorPrincipal', fn($q) => $q->where('nombre_completo', $instructor));
        }

        if ($espacio = $request->query('espacio')) {
            $query->whereHas('espacio', fn($q) => $q->where('nombre_espacio', $espacio));
        }

        if ($buscar = $request->query('buscar')) {
            $like = '%' . $buscar . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('disciplina', fn($q2) => $q2->where('nombre_disciplina', 'like', $like))
                  ->orWhereHas('instructorPrincipal', fn($q2) => $q2->where('nombre_completo', 'like', $like))
                  ->orWhereHas('espacio', fn($q2) => $q2->where('nombre_espacio', 'like', $like));
            });
        }

        // Orden estable: disciplina alfabética vía snapshot JOIN directo, luego fecha.
        $query->orderByRaw('(SELECT d.nombre_disciplina FROM disciplinas d WHERE d.id_disciplina = sesiones_activas.id_disciplina LIMIT 1)')
              ->orderBy('sesiones_activas.fecha_sesion');

        $perPage = min((int) $request->query('per_page', 30), 1000);
        $paginado = $query->paginate($perPage);

        $data = $paginado->getCollection()->map(function ($s) {
            $inscritos = (int) ($s->inscripciones_count ?? 0);

            return [
                'id_sesion'            => $s->id_sesion,
                'fecha_sesion'         => $s->fecha_sesion,
                'estatus_sesion'       => $s->estatus_sesion,
                'cantidad_inscritos'   => $inscritos,
                'inscripciones_count'  => $inscritos,
                'inscritos'            => $inscritos,
                'cupo_maximo'          => $s->cupo_maximo,
                'dia_semana'           => $s->dia_semana,
                'hora_inicio'          => $s->hora_inicio,
                'hora_fin'             => $s->hora_fin,
                'disciplina'           => $s->disciplina?->nombre_disciplina,
                'categoria'            => $s->actividadPlantilla?->disciplina?->categorias?->first()?->nombre,
                'requiere_inscripcion' => (bool) $s->requiere_inscripcion,
                'instructor'           => $s->instructorPrincipal?->nombre_completo,
                'espacio'              => $s->espacio?->nombre_espacio,
            ];
        });

        $hayPlantillaActiva = PlantillaProgramacion::where(function ($q) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('plantillas_programacion', 'estatus')) {
                $q->where('estatus', 'PUBLICADA');
            } else {
                $q->where('publicada', true)
                  ->orWhere('estatus_plantilla', true);
            }
        })->exists();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'                       => $paginado->total(),
                'per_page'                    => $paginado->perPage(),
                'current_page'                => $paginado->currentPage(),
                'last_page'                   => $paginado->lastPage(),
                'plantilla_activa_encontrada' => $hayPlantillaActiva,
            ],
        ], 200);
    }

    /**
     * GET /api/v1/programacion/resumen-ocupacion
     *
     * Resumen de ocupación e inscripciones considerando únicamente
     * sesiones de plantillas publicadas y vigentes.
     */
    public function resumenOcupacion(Request $request): JsonResponse
    {
        $query = SesionActiva::withoutGlobalScopes()
            ->publicadaYVigente()
            ->withCount([
                'inscripcionesClase as inscripciones_count' => function ($q) {
                    $q->whereIn(DB::raw('estatus_inscripcion::text'), ['CONFIRMADA', 'ASISTIO']);
                }
            ]);

        if ($fecha = $request->query('fecha')) {
            $query->where('fecha_sesion', $fecha);
        } elseif ($request->query('fecha_inicio') && $request->query('fecha_fin')) {
            $query->whereBetween('fecha_sesion', [$request->query('fecha_inicio'), $request->query('fecha_fin')]);
        }

        if ($espacioId = $request->query('id_espacio')) {
            $query->where('id_espacio', $espacioId);
        }

        if ($disciplinaId = $request->query('id_disciplina')) {
            $query->where('id_disciplina', $disciplinaId);
        }

        $sesiones = $query->get();

        $totalSesiones = $sesiones->count();
        $totalInscritos = (int) $sesiones->sum('inscripciones_count');
        $capacidadTotal = (int) $sesiones->sum('cupo_maximo');
        $porcentajeOcupacion = $capacidadTotal > 0
            ? round(($totalInscritos / $capacidadTotal) * 100, 2)
            : 0;

        return response()->json([
            'success'              => true,
            'total_sesiones'       => $totalSesiones,
            'inscripciones_count'  => $totalInscritos,
            'inscritos'            => $totalInscritos,
            'capacidad_total'      => $capacidadTotal,
            'porcentaje_ocupacion' => $porcentajeOcupacion,
            'sesiones'             => $sesiones->map(fn($s) => [
                'id_sesion'           => $s->id_sesion,
                'fecha_sesion'        => $s->fecha_sesion,
                'hora_inicio'         => $s->hora_inicio,
                'hora_fin'            => $s->hora_fin,
                'cupo_maximo'         => $s->cupo_maximo,
                'inscripciones_count' => (int) $s->inscripciones_count,
                'inscritos'           => (int) $s->inscripciones_count,
                'cantidad_inscritos'  => (int) $s->inscripciones_count,
            ]),
        ], 200);
    }

    /**
     * GET /api/v1/programacion/sesiones-activas/opciones
     * Catálogos de filtros (disciplinas, instructores, espacios, categorías)
     * de la plantilla activa, sin paginar — para poblar los selects del frontend.
     */
    public function opciones(): JsonResponse
    {
        $plantillasPublicadas = PlantillaProgramacion::where(function ($q) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('plantillas_programacion', 'estatus')) {
                $q->where('estatus', 'PUBLICADA');
            } else {
                $q->where('publicada', true)
                  ->orWhere('estatus_plantilla', true);
            }
        })->get();


        if ($plantillasPublicadas->isEmpty()) {
            return response()->json([
                'disciplinas' => [], 'instructores' => [], 'espacios' => [], 'categorias' => [],
            ]);
        }

        $idPlantillas = $plantillasPublicadas->pluck('id_plantilla');

        $actividades = DB::table('actividades_plantilla as ap')
            ->join('disciplinas as d', 'd.id_disciplina', '=', 'ap.id_disciplina')
            ->leftJoin('instructores as i', 'i.id_instructor', '=', 'ap.id_instructor')
            ->leftJoin('espacios_fisicos as e', 'e.id_espacio', '=', 'ap.id_espacio')
            ->leftJoin('categoria_disciplina as cd', 'cd.id_disciplina', '=', 'ap.id_disciplina')
            ->leftJoin('categorias as cat', 'cat.id_categoria', '=', 'cd.id_categoria')
            ->whereIn('ap.id_plantilla', $idPlantillas)
            ->select(
                'd.nombre_disciplina',
                'i.nombre_completo as instructor',
                'e.nombre_espacio as espacio',
                'cat.nombre as categoria',
            )
            ->get();

        return response()->json([
            'disciplinas'  => $actividades->pluck('nombre_disciplina')->filter()->unique()->sort()->values(),
            'instructores' => $actividades->pluck('instructor')->filter()->unique()->sort()->values(),
            'espacios'     => $actividades->pluck('espacio')->filter()->unique()->sort()->values(),
            'categorias'   => $actividades->pluck('categoria')->filter()->unique()->sort()->values(),
        ]);
    }

    /**
     * PATCH /api/v1/programacion/sesiones-activas/{id}
     * Actualiza el estatus de una sesión activa.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estatus_sesion' => 'required|in:DISPONIBLE,EN_CURSO,FINALIZADA,CANCELADA',
        ]);

        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($id);

        $anteriorEstatus = $sesion->estatus_sesion;
        $sesion->update(['estatus_sesion' => $request->estatus_sesion]);

        if ($anteriorEstatus !== 'CANCELADA' && $request->estatus_sesion === 'CANCELADA') {
            NotificarCancelacionSesionJob::dispatch($sesion->id_sesion);
        }

        return response()->json(['message' => 'Sesión actualizada correctamente.'], 200);
    }

    /**
     * GET /api/v1/programacion/sesiones-activas/{id}/asistencia
     * Lista de inscritos de una sesión (lazy — solo se llama al abrir el modal).
     */
    public function asistencia(int $id): JsonResponse
    {
        SesionActiva::withoutGlobalScopes()->findOrFail($id);

        $inscripciones = \App\Models\InscripcionClase::where('id_sesion', $id)
            ->whereIn(\DB::raw('estatus_inscripcion::text'), ['CONFIRMADA', 'ASISTIO', 'FALTA'])
            ->with([
                'socio:id_socio,nombre_completo,numero_accion',
                'miembroFamiliar:id_miembro,nombre_completo,socio_id',
                'paseInvitado.invitado:id_invitado,nombre_invitado,socio_id',
            ])
            ->orderBy('fecha_transaccion')
            ->get();

        $inscritos = $inscripciones->map(function ($ic) {
            $nombre = '—';
            $accion = '—';

            if ($ic->tipo_usuario === 'socio_titular' && $ic->socio) {
                $nombre = $ic->socio->nombre_completo;
                $accion = $ic->socio->numero_accion;
            } elseif ($ic->tipo_usuario === 'miembro_familiar' && $ic->miembroFamiliar) {
                $nombre = $ic->miembroFamiliar->nombre_completo;
                $socio = \App\Models\SocioTitular::find($ic->miembroFamiliar->socio_id);
                $accion = $socio ? $socio->numero_accion : '—';
            } elseif ($ic->tipo_usuario === 'invitado' && $ic->paseInvitado?->invitado) {
                $nombre = $ic->paseInvitado->invitado->nombre_invitado;
                $socio = \App\Models\SocioTitular::find($ic->paseInvitado->invitado->socio_id);
                $accion = $socio ? $socio->numero_accion : '—';
            }

            return [
                'id_inscripcion'      => $ic->id_inscripcion,
                'tipo_usuario'        => strtoupper((string) $ic->tipo_usuario),
                'estatus_inscripcion' => strtoupper((string) $ic->estatus_inscripcion),
                'fecha_transaccion'   => $ic->fecha_transaccion,
                'nombre_completo'     => $nombre,
                'numero_accion'       => $accion,
            ];
        });

        return response()->json(['data' => $inscritos], 200);
    }
}
