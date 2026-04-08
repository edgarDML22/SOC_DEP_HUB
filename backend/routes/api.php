<?php

use App\Http\Controllers\AgendaEspacioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular; // <-- 1. Importamos el modelo
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ReservacionController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\EspacioFisicoController;
use App\Http\Controllers\TorneoController;
use App\Http\Controllers\UpdateStatusTorneo;
use App\Http\Controllers\CreateCategories;
use App\Http\Controllers\InstructorDashboardController;
use Illuminate\Support\Facades\DB;


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

// SDH-47: Endpoint para crear torneos
Route::post('/v1/torneos', [TorneoController::class, 'store']);

// Endpoint para listar torneos 
Route::get('/v1/torneos', [TorneoController::class, 'index']);
//SDH-51: Endpoint para actualizar el estado de un torneo
Route::post('/v1/torneos/update-status', [UpdateStatusTorneo::class, 'update']);
Route::post('/v1/categories', [CreateCategories::class, 'store_categories']);

// Rutas de sistema
Route::get('/v1/system/support-link', [SystemController::class, 'getSupportLink']);
// SDH-17: Endpoint para crear reservaciones
Route::post('/v1/reservations', [ReservacionController::class, 'store']);

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

    // Ruta de prueba para verificar al usuario autenticado (Opcional)
    Route::get('/v1/user', function (Request $request) {
        return $request->user();
    });
    //  Consultar disponibilidad de espacios y clases
    Route::get('/v1/espacios/disponibilidad', [EspacioFisicoController::class, 'getAvailability']);

    // Validación de QR para Asistencia
    Route::post('/v1/asistencia/validar-qr', [AsistenciaController::class, 'validarAcceso']);

    // Dashboard dinámico del instructor
    Route::get('/v1/instructor/dashboard', [InstructorDashboardController::class, 'getDashboardData']);

    // Agregar acompañantes a una reservación
    Route::post('/v1/reservaciones/{id}/acompanantes', [ReservacionController::class, 'addAcompanante']);

    // Búsqueda dinámica de socios/familiares (Autocompletado)
    Route::get('/v1/socios/search', [SocioController::class, 'search']);

    Route::get('/v1/spaces/availability', [EspacioFisicoController::class, 'getAvailability']);
    // Consultar los horarios de un espacio fisico que han sido ocupados
    Route::get('/v1/schedules/availability', [AgendaEspacioController::class, 'getScheduleForSpace']);

    // SDH-17: Endpoint para crear reservaciones
    Route::post('/v1/reservations', [ReservacionController::class, 'store']);

    Route::post('/v1/reservations/confirm', [ReservacionController::class, 'confirm']);

    Route::post('/v1/reservations/cancel', [ReservacionController::class, 'cancel']);

    Route::get('/v1/reservations/draft/active', [ReservacionController::class, 'getActiveDraft']);

    //Ruta para actualizar el perfil del usuario
    Route::post('/v1/profile/update', [ProfileController::class, 'update']);
});




