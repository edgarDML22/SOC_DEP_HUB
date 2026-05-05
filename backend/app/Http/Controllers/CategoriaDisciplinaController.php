<?php

namespace App\Http\Controllers;

use App\Models\CategoriaDisciplina;
use App\Models\Disciplina;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Categorias;

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
            'nombre' => 'required|string|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
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
            'nombre' => 'string|unique:categorias,nombre,' . $id . ',id_categoria',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

        $categoria->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => $categoria
        ]);
    }

    public function verify_delete($id): JsonResponse
    {
        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }

        $disciplinasActivas = Disciplina::where('id_categoria', $id)
            ->where('estatus', 'ACTIVO')
            ->pluck('nombre_disciplina');

        return response()->json([
            'puede_eliminar' => $disciplinasActivas->isEmpty(),
            'disciplinas_activas' => $disciplinasActivas->count(),
            'nombres_disciplinas' => $disciplinasActivas
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $categoria = Categorias::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }

        $disciplinasActivas = Disciplina::where('id_categoria', $id)
            ->where('estatus', 'ACTIVO')
            ->pluck('nombre_disciplina');

        if ($disciplinasActivas->isNotEmpty()) {
            return response()->json([
                'puede_eliminar' => $disciplinasActivas->isEmpty(),
                'disciplinas_activas' => $disciplinasActivas->count(),
                'nombres_disciplinas' => $disciplinasActivas,
                'message' => 'No se puede eliminar la categoría porque está siendo utilizada por una o más disciplinas activas.'
            ]);
        } else {
            $categoria->update([
                'estatus' => 'INACTIVO'
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente'
            ]);
        }
    }
}
