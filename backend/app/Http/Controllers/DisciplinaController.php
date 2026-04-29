<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DisciplinaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Disciplina::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_disciplina' => 'required|string',
            'categoria_disciplina' => 'required|string',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

        $disciplina = Disciplina::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Disciplina creada correctamente',
            'data' => $disciplina
        ]);
    }

    public function show($id): JsonResponse
    {
        $disciplina = Disciplina::find($id);
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
            'categoria_disciplina' => 'string',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

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
        $disciplina->delete();
        return response()->json(['success' => true, 'message' => 'Disciplina eliminada correctamente']);
    }
}
