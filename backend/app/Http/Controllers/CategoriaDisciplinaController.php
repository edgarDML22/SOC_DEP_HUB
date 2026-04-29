<?php

namespace App\Http\Controllers;

use App\Models\CategoriaDisciplina;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriaDisciplinaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CategoriaDisciplina::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_categoria' => 'required|string|unique:categorias_disciplinas,nombre_categoria',
            'descripcion_categoria' => 'nullable|string'
        ]);

        $categoria = CategoriaDisciplina::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => $categoria
        ]);
    }

    public function show($id): JsonResponse
    {
        $categoria = CategoriaDisciplina::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $categoria = CategoriaDisciplina::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }

        $data = $request->validate([
            'nombre_categoria' => 'string|unique:categorias_disciplinas,nombre_categoria,' . $id,
            'descripcion_categoria' => 'nullable|string'
        ]);

        $categoria->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $categoria = CategoriaDisciplina::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }

        // Validación: no eliminar si está en uso
        if ($categoria->disciplinas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque está siendo utilizada por una o más disciplinas.'
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente'
        ]);
    }
}
