<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular; // <-- 1. Importamos el modelo
use App\Http\Controllers\ForgotPasswordController;

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

Route::post('/v1/auth/forgot-password', [ForgotPasswordController::class , 'sendResetLinkEmail']);
Route::middleware('auth:sanctum')->get('/v1/profile', [ProfileController::class , 'show']);
