<?php

namespace App\Http\Controllers;

use App\Models\CategoriaTorneo;
use Illuminate\Http\Request;

class CategoriaTorneoController extends Controller
{
    public function index()
    {
        try {
            $categorias = CategoriaTorneo::all();
            return response()->json([
                'success' => true,
                'data' => $categorias
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorias de torneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
