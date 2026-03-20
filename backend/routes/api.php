<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular; // <-- 1. Importamos el modelo

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 2. Ruta de prueba conectada a PostgreSQL
Route::get('/nombres', function () {
    $nombres = SocioTitular::limit(5)->pluck('nombre_completo');

    return response()->json([
        'names' => $nombres
    ]);
});