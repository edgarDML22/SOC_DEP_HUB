<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular; // <-- 1. Importamos el modelo
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\AuthController;

Route::post('/v1/auth/forgot-password', 
[ForgotPasswordController::class , 'sendResetLinkEmail']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí es donde registras las rutas API para tu aplicación.
|
*/

// ==========================================
// RUTAS PÚBLICAS
// ==========================================

// SDH-1101: Autenticación y emisión de tokens
Route::post('/v1/auth/login', [AuthController::class, 'login']);


// ==========================================
// RUTAS PROTEGIDAS (Requieren Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // SDH-1102: Revocación de tokens (Cierre de Sesión)
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

    // Ruta de prueba para verificar al usuario autenticado (Opcional)
    Route::get('/v1/user', function (Request $request) {
        return $request->user();
    });


});


