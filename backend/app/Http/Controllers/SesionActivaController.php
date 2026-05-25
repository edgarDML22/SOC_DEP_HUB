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
        $plantillaActiva = PlantillaProgramacion::where('estatus_plantilla', true)
            ->orderByDesc('fecha_inicio')
            ->first();

        if (!$plantillaActiva) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'                    => 0,
                    'per_page'                 => 30,
                    'current_page'             => 1,
                    'last_page'                => 1,
                    'plantilla_activa_encontrada' => false,
                ],
            ], 200);
        }

        // withoutGlobalScopes() es obligatorio: FuturasActivasScope excluye CANCELADA
        // y sesiones de fechas pasadas — ambas deben ser visibles en el monitoreo.
        $query = SesionActiva::withoutGlobalScopes()->with([
            'actividadPlantilla:id_actividad_plantilla,id_plantilla,id_disciplina,id_espacio,id_instructor,dia_semana,hora_inicio,hora_fin,cupo_maximo,requiere_inscripcion',
            'actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
            'actividadPlantilla.disciplina.categorias',
            'actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
            'actividadPlantilla.instructor:id_instructor,nombre_completo',
        ])
            ->whereHas('actividadPlantilla', function ($q) use ($plantillaActiva) {
                $q->where('id_plantilla', $plantillaActiva->id_plantilla);
            })
            ->whereBetween('fecha_sesion', [
                $plantillaActiva->fecha_inicio,
                $plantillaActiva->fecha_fin,
            ]);

        // ── Filtros opcionales ────────────────────────────────────────────────
        if ($estatus = $request->query('estatus')) {
            $query->where('estatus_sesion', $estatus);
        }

        if ($dia = $request->query('dia')) {
            $query->whereHas('actividadPlantilla', fn($q) => $q->where('dia_semana', $dia));
        }

        if ($horaMin = $request->query('hora_min')) {
            $query->whereHas('actividadPlantilla', fn($q) => $q->where('hora_inicio', '>=', $horaMin));
        }

        if ($horaMax = $request->query('hora_max')) {
            $query->whereHas('actividadPlantilla', fn($q) => $q->where('hora_inicio', '<=', $horaMax));
        }

        if ($tipoClase = $request->query('tipo_clase')) {
            $requiere = $tipoClase === 'CERRADA' ? 1 : 0;
            $query->whereHas('actividadPlantilla', fn($q) => $q->where('requiere_inscripcion', $requiere));
        }

        if ($disciplina = $request->query('disciplina')) {
            $query->whereHas('actividadPlantilla.disciplina', fn($q) => $q->where('nombre_disciplina', $disciplina));
        }

        if ($categoria = $request->query('categoria')) {
            $query->whereHas('actividadPlantilla.disciplina.categorias', fn($q) => $q->where('nombre', $categoria));
        }

        if ($instructor = $request->query('instructor')) {
            $query->whereHas('actividadPlantilla.instructor', fn($q) => $q->where('nombre_completo', $instructor));
        }

        if ($espacio = $request->query('espacio')) {
            $query->whereHas('actividadPlantilla.espacioFisico', fn($q) => $q->where('nombre_espacio', $espacio));
        }

        if ($buscar = $request->query('buscar')) {
            $like = '%' . $buscar . '%';
            $query->whereHas('actividadPlantilla', function ($q) use ($like) {
                $q->whereHas('disciplina', fn($q2) => $q2->where('nombre_disciplina', 'like', $like))
                  ->orWhereHas('instructor', fn($q2) => $q2->where('nombre_completo', 'like', $like))
                  ->orWhereHas('espacioFisico', fn($q2) => $q2->where('nombre_espacio', 'like', $like));
            });
        }

        // Orden estable: disciplina alfabética, luego fecha.
        // Se usa un subquery escalar para el ORDER BY y se omite el JOIN de tablas
        // extra para evitar filas duplicadas (categorías tienen relación n:m con disciplinas).
        $query->orderByRaw('(SELECT d.nombre_disciplina FROM actividades_plantilla ap JOIN disciplinas d ON d.id_disciplina = ap.id_disciplina WHERE ap.id_actividad_plantilla = sesiones_activas.id_actividad_plantilla LIMIT 1)')
              ->orderBy('sesiones_activas.fecha_sesion');

        $perPage = min((int) $request->query('per_page', 30), 1000);
        $paginado = $query->paginate($perPage);

        $data = $paginado->getCollection()->map(fn($s) => [
            'id_sesion'            => $s->id_sesion,
            'fecha_sesion'         => $s->fecha_sesion,
            'estatus_sesion'       => $s->estatus_sesion,
            'cantidad_inscritos'   => $s->cantidad_inscritos,
            'dia_semana'           => $s->actividadPlantilla?->dia_semana,
            'hora_inicio'          => $s->actividadPlantilla?->hora_inicio,
            'hora_fin'             => $s->actividadPlantilla?->hora_fin,
            'disciplina'           => $s->actividadPlantilla?->disciplina?->nombre_disciplina,
            'categoria'            => $s->actividadPlantilla?->disciplina?->categorias?->first()?->nombre,
            'requiere_inscripcion' => (bool) $s->actividadPlantilla?->requiere_inscripcion,
            'instructor'           => $s->actividadPlantilla?->instructor?->nombre_completo,
            'espacio'              => $s->actividadPlantilla?->espacioFisico?->nombre_espacio,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'                    => $paginado->total(),
                'per_page'                 => $paginado->perPage(),
                'current_page'             => $paginado->currentPage(),
                'last_page'                => $paginado->lastPage(),
                'plantilla_activa_encontrada' => true,
            ],
        ], 200);
    }

    /**
     * GET /api/v1/programacion/sesiones-activas/opciones
     * Catálogos de filtros (disciplinas, instructores, espacios, categorías)
     * de la plantilla activa, sin paginar — para poblar los selects del frontend.
     */
    public function opciones(): JsonResponse
    {
        $plantillaActiva = PlantillaProgramacion::where('estatus_plantilla', true)
            ->orderByDesc('fecha_inicio')
            ->first();

        if (!$plantillaActiva) {
            return response()->json([
                'disciplinas' => [], 'instructores' => [], 'espacios' => [], 'categorias' => [],
            ]);
        }

        $actividades = DB::table('actividades_plantilla as ap')
            ->join('disciplinas as d', 'd.id_disciplina', '=', 'ap.id_disciplina')
            ->leftJoin('instructores as i', 'i.id_instructor', '=', 'ap.id_instructor')
            ->leftJoin('espacios_fisicos as e', 'e.id_espacio', '=', 'ap.id_espacio')
            ->leftJoin('categoria_disciplina as cd', 'cd.id_disciplina', '=', 'ap.id_disciplina')
            ->leftJoin('categorias as cat', 'cat.id_categoria', '=', 'cd.id_categoria')
            ->where('ap.id_plantilla', $plantillaActiva->id_plantilla)
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
            ->whereIn('estatus_inscripcion', ['CONFIRMADA', 'ASISTIO', 'FALTA'])
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
                'tipo_usuario'        => strtoupper($ic->tipo_usuario),
                'estatus_inscripcion' => $ic->estatus_inscripcion,
                'fecha_transaccion'   => $ic->fecha_transaccion,
                'nombre_completo'     => $nombre,
                'numero_accion'       => $accion,
            ];
        });

        return response()->json(['data' => $inscritos], 200);
    }
}
