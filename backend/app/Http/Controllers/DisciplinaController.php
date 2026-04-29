<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use App\Models\SesionActiva;
use App\Models\torneos;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DisciplinaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Disciplina::with(['categoria'])->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_disciplina' => 'required|string',
            'id_categoria' => 'required|exists:categorias_disciplinas,id',
            'categoria_disciplina' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

        // Sincronizar nombre de categoría para compatibilidad con el ENUM legado si es necesario
        if ($request->id_categoria) {
            $cat = \App\Models\CategoriaDisciplina::find($request->id_categoria);
            if ($cat) {
                $data['categoria_disciplina'] = $cat->nombre_categoria;
            }
        }

        $disciplina = Disciplina::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Disciplina creada correctamente',
            'data' => $disciplina
        ]);
    }

    public function show($id): JsonResponse
    {
        $disciplina = Disciplina::with(['instructores', 'categoria'])->find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $disciplina]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $disciplina = Disciplina::find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }

        $data = $request->validate([
            'nombre_disciplina' => 'string',
            'id_categoria' => 'exists:categorias_disciplinas,id',
            'categoria_disciplina' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

        // Si se intenta deshabilitar o poner en mantenimiento, verificar dependencias
        if (isset($data['estatus']) && 
            ($data['estatus'] === 'DESHABILITADO' || $data['estatus'] === 'MANTENIMIENTO') && 
            $disciplina->estatus !== $data['estatus']) {
            
            if ($this->hasActiveDependencies($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede deshabilitar la disciplina porque tiene sesiones o torneos activos vinculados.'
                ], 422);
            }
        }

        // Sincronizar nombre de categoría para compatibilidad con el ENUM legado
        if (isset($data['id_categoria'])) {
            $cat = \App\Models\CategoriaDisciplina::find($data['id_categoria']);
            if ($cat) {
                $data['categoria_disciplina'] = $cat->nombre_categoria;
            }
        }

        $disciplina->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Disciplina actualizada correctamente',
            'data' => $disciplina
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $disciplina = Disciplina::find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }

        if ($this->hasActiveDependencies($id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede deshabilitar la disciplina porque tiene sesiones o torneos activos vinculados.'
            ], 422);
        }

        $disciplina->update(['estatus' => 'DESHABILITADO']);

        return response()->json([
            'success' => true, 
            'message' => 'Disciplina deshabilitada correctamente'
        ]);
    }

    private function hasActiveDependencies($id_disciplina): bool
    {
        $today = now()->toDateString();

        // 1. Sesiones activas futuras
        $hasSessions = SesionActiva::whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
            ->where('fecha_sesion', '>=', $today)
            ->whereHas('actividadPlantilla', function ($query) use ($id_disciplina) {
                $query->where('id_disciplina', $id_disciplina);
            })
            ->exists();

        if ($hasSessions) return true;

        // 2. Torneos futuros o activos
        $hasTournaments = torneos::where('id_disciplina', $id_disciplina)
            ->where('fecha_fin', '>=', $today)
            ->whereNotIn('estatus_torneo', ['CANCELADO', 'FINALIZADO'])
            ->exists();

        return $hasTournaments;
    }
}
