<?php

namespace App\Http\Controllers;

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
     * Lista las sesiones activas pertenecientes a la plantilla ACTIVA vigente,
     * filtrando las sesiones cuya fecha_sesion cae dentro del rango
     * [fecha_inicio, fecha_fin] de dicha plantilla. Ordena alfabéticamente
     * por nombre de disciplina e incluye la categoría asociada.
     */
    public function index(): JsonResponse
    {
        $plantillaActiva = PlantillaProgramacion::where('estatus_plantilla', true)
            ->orderByDesc('fecha_inicio')
            ->first();

        if (!$plantillaActiva) {
            return response()->json(['data' => []], 200);
        }

        $sesiones = SesionActiva::with([
            'actividadPlantilla:id_actividad_plantilla,id_plantilla,id_disciplina,id_espacio,id_instructor,dia_semana,hora_inicio,hora_fin',
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
            ])
            ->get()
            ->sortBy(fn($s) => mb_strtolower($s->actividadPlantilla?->disciplina?->nombre_disciplina ?? 'zzz'))
            ->values()
            ->map(fn($s) => [
                'id_sesion'          => $s->id_sesion,
                'fecha_sesion'       => $s->fecha_sesion,
                'estatus_sesion'     => $s->estatus_sesion,
                'cantidad_inscritos' => $s->cantidad_inscritos,
                'dia_semana'         => $s->actividadPlantilla?->dia_semana,
                'hora_inicio'        => $s->actividadPlantilla?->hora_inicio,
                'hora_fin'           => $s->actividadPlantilla?->hora_fin,
                'disciplina'           => $s->actividadPlantilla?->disciplina?->nombre_disciplina,
                'categoria'            => $s->actividadPlantilla?->disciplina?->categorias?->first()?->nombre,
                'requiere_inscripcion' => (bool) $s->actividadPlantilla?->requiere_inscripcion,
                'instructor'           => $s->actividadPlantilla?->instructor?->nombre_completo,
                'espacio'              => $s->actividadPlantilla?->espacioFisico?->nombre_espacio,
            ]);

        return response()->json(['data' => $sesiones], 200);
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
        $sesion->update(['estatus_sesion' => $request->estatus_sesion]);

        return response()->json(['message' => 'Sesión actualizada correctamente.'], 200);
    }

    /**
     * GET /api/v1/programacion/sesiones-activas/{id}/asistencia
     * Lista de inscritos de una sesión (lazy — solo se llama al abrir el modal).
     */
    public function asistencia(int $id): JsonResponse
    {
        SesionActiva::withoutGlobalScopes()->findOrFail($id);

        $inscritos = DB::table('inscripciones_clases as ic')
            ->leftJoin('socios_titulares as st', 'ic.id_usuario', '=', 'st.id_socio')
            ->where('ic.id_sesion', $id)
            ->select(
                'ic.id_inscripcion',
                'ic.tipo_usuario',
                'ic.estatus_inscripcion',
                'ic.fecha_transaccion',
                'st.nombre_completo',
                'st.numero_accion',
            )
            ->orderBy('ic.fecha_transaccion')
            ->get();

        return response()->json(['data' => $inscritos], 200);
    }
}
