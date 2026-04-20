<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\WalkInController;
use App\Http\Controllers\WorkingDayController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Broadcast;

// ─── PÚBLICO ──────────────────────────────────────────────────────────────────
Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login',    [ApiAuthController::class, 'login']);

// ─── AUTENTICADO (cualquier rol) ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

     Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return Broadcast::auth($request);
    });

    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me',      [ApiAuthController::class, 'me']);
    Route::post('/me/update', [ApiAuthController::class, 'updateProfile']);
    Route::put('/me/password', [ApiAuthController::class, 'updatePassword']);

    // Catálogo de servicios (solo activos, lectura)
    Route::get('/services', [ServiceController::class, 'index']);

    Route::get('/petsi', [PetController::class, 'index']);

    // Notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/',            [NotificationController::class, 'index']);
        Route::get('/unread',      [NotificationController::class, 'unread']);
        Route::patch('/read-all',  [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}',     [NotificationController::class, 'destroy']);
    });

    // Días laborales y horarios (cualquier rol)
    Route::get('/working-days',        [WorkingDayController::class, 'index']);
    Route::get('/working-days/{id}',   [WorkingDayController::class, 'show']);
    Route::get('/time-slots',          [TimeSlotController::class, 'index']);
    Route::get('/time-slots/{id}',     [TimeSlotController::class, 'show']);

    // Historial médico
    Route::get('/medical-records',     [MedicalRecordController::class, 'index']);
    Route::get('/medical-records/{id}',[MedicalRecordController::class, 'show']);

    // Citas
    Route::get('/appointments',        [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}',   [AppointmentController::class, 'show']);
});

// ─── ADMIN (role 1) ───────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {

    // Gestión de usuarios
    Route::get('/admin/users',          [UserController::class, 'index']);
    Route::post('/admin/users',         [UserController::class, 'store']);
    Route::get('/admin/users/{id}',     [UserController::class, 'show']);
    Route::put('/admin/users/{id}',     [UserController::class, 'update']);
    Route::delete('/admin/users/{id}',  [UserController::class, 'destroy']);

    // Gestión de empleados
    Route::get('/admin/employees',         [UserController::class, 'employees']);
    Route::get('/admin/employees/{id}',    [UserController::class, 'showEmployee']);

    // Catalogo de servicios (gestion completa)
    Route::get('/admin/services',          [ServiceController::class, 'indexAdmin']);
    Route::post('/admin/services',         [ServiceController::class, 'store']);
    Route::get('/admin/services/{id}',     [ServiceController::class, 'show']);
    Route::put('/admin/services/{id}',     [ServiceController::class, 'update']);
    Route::delete('/admin/services/{id}',  [ServiceController::class, 'destroy']);

    // Gestion de calendario
    // Working Days
    Route::get('/admin/working-days', [WorkingDayController::class, 'index']);
    Route::post('/admin/working-days/generate-month', [WorkingDayController::class, 'generateMonth']);
    Route::get('/admin/working-days/{workingDay}', [WorkingDayController::class, 'show']);
    Route::patch('/admin/working-days/{workingDay}/toggle-open', [WorkingDayController::class, 'toggleOpen']);
    Route::delete('/admin/working-days/{workingDay}', [WorkingDayController::class, 'destroy']);

    // Time Slots
    Route::get('/admin/working-days/{workingDay}/time-slots', [TimeSlotController::class, 'index']);
    Route::get('/admin/time-slots/{timeSlot}', [TimeSlotController::class, 'show']);
    Route::patch('/admin/time-slots/{timeSlot}/toggle-open', [TimeSlotController::class, 'toggleOpen']);
    Route::patch('/admin/time-slots/{timeSlot}/status', [TimeSlotController::class, 'updateStatus']);
    Route::patch('/admin/working-days/{workingDay}/time-slots/disable-all', [TimeSlotController::class, 'disableAllForDay']);
    Route::patch('/admin/working-days/{workingDay}/time-slots/enable-all', [TimeSlotController::class, 'enableAllForDay']);

    // Walk-in
    Route::post('/walk-in',               [WalkInController::class, 'store']);
    Route::get('/admin/veterinarios', [UserController::class, 'veterinarians']);

    // Mascotas (gestion completa)
    Route::get('/admin1/pets',          [PetController::class, 'index']);
    Route::post('/admin/pets',         [PetController::class, 'store']);
    Route::get('/admin1/pets/{id}',     [PetController::class, 'show']);
    Route::put('/admin/pets/{id}',     [PetController::class, 'update']);
    Route::delete('/admin/pets/{id}',  [PetController::class, 'destroy']);
});

//appointments empleado - admin
Route::middleware(['auth:sanctum', 'role:1,2,4'])->group(function () {
    Route::post('/appointments',          [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

// ─── EMPLEADO / RECEPCIONISTA (role 2) ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:2'])->group(function () {
    Route::get('/recep/pets',          [PetController::class, 'index']);
    Route::get('/recep/pets/{id}',     [PetController::class, 'show']);
    // Clientes
    Route::get('/empleado/clients',       [UserController::class, 'clients']);
    Route::get('/empleado/clients/{id}',  [UserController::class, 'showClient']);

    // Walk-in (CU-20)
    Route::post('/recep/walk-in',              [WalkInController::class, 'store']);

    // Mascotas
    Route::apiResource('/pets', PetController::class);

    Route::get('/recep/appointments',        [AppointmentController::class, 'index']);
    Route::get('/recep/appointments/{id}',   [AppointmentController::class, 'show']);
    Route::post('/recep/appointments',          [AppointmentController::class, 'store']);
    Route::put('/recep/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/recep/appointments/{id}', [AppointmentController::class, 'destroy']);

    // 👇 Agrega esta línea
    Route::get('/empleado/veterinarios', [UserController::class, 'veterinarians']);
});

// ─── CITAS: Cliente, Recepcionista y Admin (roles 1, 2, 3) ───────────────────
Route::middleware(['auth:sanctum', 'role:1,2,3'])->group(function () {
    Route::post('/appointments',        [AppointmentController::class, 'store']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:1,2,4'])->group(function () {
    Route::put('/appointments/{id}',    [AppointmentController::class, 'update']);
});

// ─── WALK-IN: Admin (role 1) ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {
    Route::post('/walk-in',             [WalkInController::class, 'store']);
});

// ─── VETERINARIO (role 4) ─────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:4'])->group(function () {

    // Expedientes médicos (creación y edición)
    Route::post('/medical-records',        [MedicalRecordController::class, 'store']);
    Route::put('/medical-records/{id}',    [MedicalRecordController::class, 'update']);

    // Mascotas (lectura)
    Route::get('/pets',     [PetController::class, 'index']);
    Route::get('/pets/{id}',[PetController::class, 'show']);
});

// ─── CLIENTE (role 3) ─────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:3'])->group(function () {

    // Sus mascotas
    Route::get('/mis-mascotas',            [PetController::class, 'index']);
    Route::get('/mis-mascotas/{id}',       [PetController::class, 'show']);
    Route::post('/mis-mascotas',           [PetController::class, 'store']);
    Route::put('/mis-mascotas/{id}',       [PetController::class, 'update']);
    Route::delete('/mis-mascotas/{id}', [PetController::class, 'destroy']);

    // Sus citas
    Route::get('/client/appointments', [AppointmentController::class, 'index']);
    Route::get('/client/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/cliente/appointments/{id}', [AppointmentController::class, 'update']);
    Route::post('/cliente/appointments',          [AppointmentController::class, 'store']);

      // Perfil
    Route::get('/perfil',    [PerfilController::class, 'show']);
    Route::post('/perfil',   [PerfilController::class, 'update']);
    Route::delete('/perfil', [PerfilController::class, 'destroy']);
    Route::delete('/cliente/appointments/{id}', [AppointmentController::class, 'destroy']);
});



