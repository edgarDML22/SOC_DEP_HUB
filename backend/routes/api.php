<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular; // <-- 1. Importamos el modelo
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrController;

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

// Recuperación de contraseña
Route::post('/v1/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);

// SDH-77: Endpoint para restablecimiento de contraseña
Route::post('/v1/auth/reset-password', [ResetPasswordController::class, 'resetPassword']);

// Rutas de sistema
Route::get('/v1/system/support-link', [SystemController::class, 'getSupportLink']);

// 2. Ruta de prueba conectada a PostgreSQL (Añadida desde Incoming)
Route::get('/nombres', function () {
    $nombres = SocioTitular::limit(5)->pluck('nombre_completo');

    return response()->json([
        'names' => $nombres
    ]);
});


// ==========================================
// RUTAS PROTEGIDAS (Requieren Token)
// ==========================================

// Ruta por defecto que incluye Laravel
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Grupo protegido con Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // SDH-1102: Revocación de tokens (Cierre de Sesión)
    Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

    // Perfil del usuario
    Route::get('/v1/profile', [ProfileController::class, 'show']);

    // Generar payload encriptado para QR
    Route::get('/v1/profile/qr-data', [QrController::class, 'generateQrPayload']);

    // Ruta de prueba para verificar al usuario autenticado (Opcional)
    Route::get('/v1/user', function (Request $request) {
        return $request->user();
    });

});