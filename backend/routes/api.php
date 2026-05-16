<?php

use App\Http\Controllers\AgendaEspacioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\SocioTitular;
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
use App\Http\Controllers\InstructorController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\GuestPassController;
use App\Http\Controllers\GuestStatusController;
use App\Http\Controllers\AdminFamilyController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\LudotecaController;
use App\Http\Controllers\LudotecaStatusController;
use App\Http\Controllers\LudotecaRegisterController;
use App\Http\Controllers\MiembrosFamiliaresList;
use App\Http\Controllers\MiembrosFamiliaresController;
use App\Http\Controllers\RegisterEventController;
use App\Http\Controllers\DisciplinaController;
use App\Http\Controllers\CategoriaDisciplinaController;
use App\Http\Controllers\AdminLudotecaController;
use App\Http\Controllers\EncuestaLudotecaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ReservationAdminController;
use App\Http\Controllers\UserAdminController;

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



Route::post('/v1/categories', [CreateCategories::class, 'store_categories']);

// Rutas de sistema
Route::get('/v1/system/support-link', [SystemController::class, 'getSupportLink']);
// SDH-17: Endpoint para crear reservaciones
Route::post('/v1/reservations', [ReservacionController::class, 'store']);
// SDH 187: Actualizar estatus de la cuenta del socio
Route::patch('/v1/socios/{id}/estatus-cuenta', [SocioController::class, 'updateEstatusController']);



Route::post('/v1/reservations', [ReservacionController::class, 'store']);// Miembros Familiares
// Route::get('/v1/miembros-familiares', [AdminFamilyController::class, 'show']);

// 2. Ruta de prueba conectada a PostgreSQL (Añadida desde Incoming)


// SDH-164 protec por middleware que el insturctor que tenga el turno pueda acceder a estas rutas
Route::middleware(['check.turno'])->group(function () {
    Route::get('/v1/ludoteca/test-turno-ludoteca', function () {
        return response()->json([
            'message' => 'Middleware funcionando correctamente'
        ]);
    });
});

Route::delete('/v1/instructores/{id}/disciplinas/{disciplina_id}', [InstructorController::class, 'deleteRelationshipDiscipline']);
// RUTAS PROTEGIDAS (Requieren Token)
// ==========================================
// Ruta por defecto que incluye Laravel
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::patch('v1/ludoteca/estancia/{id}/status', [LudotecaStatusController::class, 'updateStatus']);
// Grupo protegido con Sanctum
// SDH-1102: Logout fuera del grupo auth — el controller maneja tokens inválidos o ausentes
Route::post('/v1/auth/logout', [AuthController::class, 'logout']);

//SDH-267: Pre-registro de torneos
Route::post(
    '/v1/torneos/{id}/pre-registros',
    [TorneoController::class, 'preRegistro']
);

Route::middleware('auth:sanctum')->group(function () {

    // Perfil del usuario
    Route::get('/v1/profile', [ProfileController::class, 'show']);

    // Obtener QR del usuario
    Route::get('/v1/profile/qr-data', [QrController::class, 'generateQrPayload']);

    // Ruta de prueba para verificar al usuario autenticado (Opcional)
    Route::get('/v1/user', function (Request $request) {
        return $request->user();
    });
    //  Consultar disponibilidad de espacios y clases

    // Validación de QR para Asistencia
    Route::post('/v1/asistencia/validar-qr', [AsistenciaController::class, 'validarAcceso']);

    //CRUD INSTRUCTORES
    // Para instructor
    // Dashboard dinámico del instructor
    Route::get('/v1/instructor/dashboard', [InstructorController::class, 'getDashboardData']);
    // Profile del instructor
    Route::get('/v1/instructor/profile', [InstructorController::class, 'getProfileData']);
    // Para admin
    // Get all instructors
    Route::get('/v1/instructors/all', [InstructorController::class, 'getAllInstructors']);
    // Create instructor
    Route::post('/v1/instructors/create', [InstructorController::class, 'store']);
    // Get single instructor
    Route::get('/v1/instructors/{id}', [InstructorController::class, 'show']);
    // Update instructor
    Route::put('/v1/instructors/update/{id}', [InstructorController::class, 'update']);
    // Delete instructor (Logical delete/Inactivate)
    Route::delete('/v1/instructors/delete/{id}', [InstructorController::class, 'destroy']);

    // Meticulous Status Management
    Route::get('/v1/instructors/{id}/status-impact', [InstructorController::class, 'getActivitiesImpact']);
    Route::get('/v1/activities/{activityId}/substitutes', [InstructorController::class, 'getCandidateSubstitutes']);
    Route::post('/v1/instructors/{id}/apply-status', [InstructorController::class, 'applyStatusChange']);

    // CRUD SOCIOS (rutas estáticas antes del parámetro dinámico {id})
    Route::get('/v1/socios/all', [SocioController::class, 'index']);
    Route::get('/v1/socios/search', [SocioController::class, 'search']);
    Route::get('/v1/socios/{id}', [SocioController::class, 'show']);
    Route::put('/v1/socios/update/{id}', [SocioController::class, 'update']);

    // CRUD DISCIPLINAS
    Route::get('/v1/disciplinas/all', [DisciplinaController::class, 'index']);
    Route::get('/v1/disciplinas/{id}', [DisciplinaController::class, 'show']);
    Route::post('/v1/disciplinas/create', [DisciplinaController::class, 'store']);
    Route::put('/v1/disciplinas/update/{id}', [DisciplinaController::class, 'update']);
    Route::patch('/v1/disciplinas/{id}/estatus', [DisciplinaController::class, 'update']);
    Route::delete('/v1/disciplinas/delete/{id}', [DisciplinaController::class, 'destroy']);

    // CRUD CATEGORIAS DISCIPLINAS
    Route::get('/v1/disciplinas-categories/all', [CategoriaDisciplinaController::class, 'index']);
    Route::post('/v1/disciplinas-categories/create', [CategoriaDisciplinaController::class, 'store']);
    Route::get('/v1/disciplinas-categories/{id}', [CategoriaDisciplinaController::class, 'show']);
    Route::put('/v1/disciplinas-categories/update/{id}', [CategoriaDisciplinaController::class, 'update']);
    Route::delete('/v1/disciplinas-categories/delete/{id}', [CategoriaDisciplinaController::class, 'destroy']);
    Route::get('/v1/categorias/{id}/verificar-eliminacion', [CategoriaDisciplinaController::class, 'verify_delete']);


    // CRUD ESPACIOS
    Route::get('/v1/spaces/availability', [EspacioFisicoController::class, 'getAvailability']);
    Route::get('/v1/spaces/all', [EspacioFisicoController::class, 'index']);
    Route::get('/v1/spaces/{id}', [EspacioFisicoController::class, 'show']);
    Route::post('/v1/spaces/create', [EspacioFisicoController::class, 'store']);
    Route::patch('/v1/espacios/{id}/estatus', [EspacioFisicoController::class, 'update']);
    Route::delete('/v1/spaces/delete/{id}', [EspacioFisicoController::class, 'destroy']);

    // Rutas de utilidad/negocio
    Route::get('/v1/schedules/availability', [AgendaEspacioController::class, 'getScheduleForSpace']);
    Route::post('/v1/reservaciones/{id}/acompanantes', [ReservacionController::class, 'addAcompanante']);
    Route::put('/v1/reservations/{id}/draft/acompanantes', [ReservacionController::class, 'syncAcompanantesDraft']);

    // RESERVACIONES ON DEMAND
    Route::post('/v1/reservations', [ReservacionController::class, 'store']);

    Route::post('/v1/reservations/confirm', [ReservacionController::class, 'confirm']);

    Route::post('/v1/reservations/cancel', [ReservacionController::class, 'cancel']);

    Route::post('/v1/reservations/discard', [ReservacionController::class, 'discard']);


    Route::get('/v1/reservations/draft/active', [ReservacionController::class, 'getActiveDraft']);
    Route::get('/v1/reservations/my-list', [ReservacionController::class, 'myReservations']);
    Route::get('/v1/reservations/admin/list', [ReservationAdminController::class, 'index']);
    // SDH 226: Obtener filtros de metadatos para reservaciones
    Route::get('/v1/reservations/admin/filters-meta', [ReservationAdminController::class, 'filterMeta']);
    Route::get('/v1/reservations/admin/stats', [ReservationAdminController::class, 'getStats']);

    //Ruta para actualizar el perfil del usuario
    Route::post('/v1/profile/update', [ProfileController::class, 'update']);

    Route::get(
        '/v1/ludoteca/encuesta/{idHistorial}',
        [EncuestaLudotecaController::class, 'obtenerEncuesta']
    );

    Route::post(
        '/v1/ludoteca/encuesta/{idHistorial}',
        [EncuestaLudotecaController::class, 'guardarEncuesta']
    );

    // GUESTS 
    Route::post('/v1/guest-create', [GuestStatusController::class, 'store']);
    Route::get('/v1/guest-list', [GuestStatusController::class, 'show']);
    Route::put('/v1/guests/{id}', [GuestStatusController::class, 'update']);
    Route::delete('/v1/guests/{id}', [GuestStatusController::class, 'destroy']);
    Route::put('/v1/guests/{id}/restore', [GuestStatusController::class, 'restore']);
    Route::put('/v1/guests/{id}/toggle-pass', [GuestStatusController::class, 'togglePass']);


    // FAMILY MEMBERS (Socio autenticado)
    Route::get('/v1/family-member-list', [MiembrosFamiliaresController::class, 'show']);
    Route::post('/v1/family-member-create', [MiembrosFamiliaresController::class, 'store']);
    Route::put('/v1/family-member/{id}', [MiembrosFamiliaresController::class, 'update']);
    Route::delete('/v1/family-member/{id}', [MiembrosFamiliaresController::class, 'destroy']);

    // FAMILY MEMBERS SDH 240
    Route::prefix('v1/admin/socios/{socioId}/familiares')->group(function () {
        Route::get('/', [AdminFamilyController::class, 'index']);
        Route::post('/', [AdminFamilyController::class, 'store']);
        Route::put('/{id}', [AdminFamilyController::class, 'update']);
        Route::delete('/{id}', [AdminFamilyController::class, 'destroy']);
    });



    // FRIENDS
    Route::get('/v1/friends-list', [FriendsController::class, 'show']);
    Route::post('/v1/friend-add', [FriendsController::class, 'store']);
    Route::delete('/v1/friend-remove', [FriendsController::class, 'destroy']);
    Route::delete('/v1/friend-cancel', [FriendsController::class, 'cancel']);
    Route::post('/v1/friend-accept', [FriendsController::class, 'accept']);
    Route::post('/v1/friend-reject', [FriendsController::class, 'reject']);


    Route::get('/v1/instructor/sessions', [SessionController::class, 'index']);

    // SDH-23: Register event (Asistencia de sesión)
    Route::post('/v1/instructor/register-event', [RegisterEventController::class, 'register_event']);
    Route::put('/v1/guests/passes/{id}/cancel', [GuestPassController::class, 'cancelPass']);

    // ==========================================
    // LUDOTECA (RUTAS PROTEGIDAS)
    // ==========================================

    Route::prefix('v1/ludoteca')->group(function () {
        // Operativas (instructor / socio)
        Route::get('validar-tutor', [LudotecaController::class, 'validarTutor']);
        Route::post('register', [LudotecaRegisterController::class, 'store']);
        Route::get('list', [MiembrosFamiliaresList::class, 'show']);
        Route::post('ingreso', [LudotecaStatusController::class, 'checkIn']);

        // Administrativas (gerente )
        Route::post('admin/turnos', [AdminLudotecaController::class, 'store']);
        Route::get('admin/turnos', [AdminLudotecaController::class, 'getTurnos']);
        Route::get('admin/instructores', [AdminLudotecaController::class, 'getInstructores']);
        Route::get('admin/stats', [AdminLudotecaController::class, 'getStats']);
        Route::get('admin/socios-con-menores', [AdminLudotecaController::class, 'getSociosConMenores']);
        Route::get('admin/historial', [AdminLudotecaController::class, 'getHistorial']);
    });

    //SDH-248:CRUD GERENTES
    Route::prefix('v1/admin/users')
        ->middleware('auth:sanctum')
        ->group(function () {

            Route::get('/', [UserAdminController::class, 'index']);

            Route::post('/', [UserAdminController::class, 'store']);

            Route::put('/{id}', [UserAdminController::class, 'update']);

            Route::patch(
                '/{id}/toggle-activo',
                [UserAdminController::class, 'toggleActivo']
            );
        });


    Route::get('/v1/socio/ludoteca/status', [LudotecaStatusController::class, 'getChildrenStatus']);

    // RUTAS DE SANCIONES
    Route::post('/v1/sanciones/ludoteca', [\App\Http\Controllers\Sanciones::class, 'aplicarSancionesAPI']);
    Route::post('/v1/sanciones/reservas', [\App\Http\Controllers\Sanciones::class, 'aplicarSancionesReservasAPI']);

    // RUTAS DE NOTIFICACIONES (socio autenticado)
    Route::get('/v1/notificaciones', [\App\Http\Controllers\NotificacionController::class, 'index']);
    Route::patch('/v1/notificaciones/{id}/leer', [\App\Http\Controllers\NotificacionController::class, 'marcarLeida']);
    Route::patch('/v1/notificaciones/leer-todas', [\App\Http\Controllers\NotificacionController::class, 'marcarTodasLeidas']);

    //TORNEOS ACTUALIZACION, SDH 267

    Route::prefix('v1/torneos')->group(function () {

        Route::post('/', [TorneoController::class, 'store']);

        Route::get('/', [TorneoController::class, 'index']);

        Route::patch('/{id}/status', [UpdateStatusTorneo::class, 'update']);
    });

    Route::prefix('v1/encuentros')->group(function () {

        //
    });




});





