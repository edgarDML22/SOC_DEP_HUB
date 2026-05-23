<?php

namespace App\Http\Controllers;

use App\Models\SesionActiva;
use Illuminate\Http\JsonResponse;

class SesionActivaController extends Controller
{
    /**
     * GET /api/v1/programacion/sesiones-activas
     *
     * Lista las sesiones activas con datos desnormalizados (disciplina, instructor, espacio)
     * para el monitoreo semanal del subgerente. Excluye sesiones pasadas por defecto
     * gracias al FuturasActivasScope del modelo.
     */
    public function index(): JsonResponse
    {
        $sesiones = SesionActiva::with([
            'actividadPlantilla:id_actividad_plantilla,id_disciplina,id_espacio,id_instructor,dia_semana,hora_inicio,hora_fin',
            'actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
            'actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
            'actividadPlantilla.instructor:id_instructor,nombre_completo',
        ])
        ->orderBy('fecha_sesion')
        ->get()
        ->map(fn($s) => [
            'id_sesion'          => $s->id_sesion,
            'fecha_sesion'       => $s->fecha_sesion,
            'estatus_sesion'     => $s->estatus_sesion,
            'cantidad_inscritos' => $s->cantidad_inscritos,
            'dia_semana'         => $s->actividadPlantilla?->dia_semana,
            'hora_inicio'        => $s->actividadPlantilla?->hora_inicio,
            'hora_fin'           => $s->actividadPlantilla?->hora_fin,
            'disciplina'         => $s->actividadPlantilla?->disciplina?->nombre_disciplina,
            'instructor'         => $s->actividadPlantilla?->instructor?->nombre_completo,
            'espacio'            => $s->actividadPlantilla?->espacioFisico?->nombre_espacio,
        ]);

        return response()->json(['data' => $sesiones], 200);
    }
}
