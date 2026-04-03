<?php


namespace App\Http\Controllers;

use App\Models\CategoriaTorneo;
use Illuminate\Http\Request;
use App\Http\Requests;

class CreateCategories extends Controller
{
    public function store_categories(Request $request)
    {
        // Validar campos
        $request->validate([
            'nombre_categoria' => 'required|string|max:100',
            'edad_maxima' => 'required|integer|min:1',
            'edad_minima' => 'required|integer|min:1',
            'genero_requerido' => 'required|in:M,F,MIXTO',
        ]);
        $existe = CategoriaTorneo::where('nombre_categoria', $request->nombre_categoria)
            ->where('edad_maxima', $request->edad_maxima)
            ->where('edad_minima', $request->edad_minima)
            ->where('genero_requerido', $request->genero_requerido)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un torneo con ese nombre en esa fecha'
            ], 409);
        }


        // Crear categoria  
        $categoria = CategoriaTorneo::create([
            'nombre_categoria' => $request->nombre_categoria,
            'edad_maxima' => $request->edad_maxima,
            'edad_minima' => $request->edad_minima,
            'genero_requerido' => $request->genero_requerido,
        ]);

        return response()->json([
            'success' => true,
            'categoria' => $categoria,
        ]);
    }
}