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
use App\Http\Controllers\PerfilController;

// ─── PÚBLICO ──────────────────────────────────────────────────────────────────
Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login',    [ApiAuthController::class, 'login']);

// ─── AUTENTICADO (cualquier rol) ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me',      [ApiAuthController::class, 'me']);

    // Perfil propio
    Route::get('/perfil',              [PerfilController::class, 'show']);
    Route::post('/perfil',             [PerfilController::class, 'update']);
    Route::post('/perfil/password',    [PerfilController::class, 'changePassword']);
    Route::delete('/perfil',           [PerfilController::class, 'destroy']);

    // Servicios activos (lectura pública para todos los roles)
    Route::get('/services',            [ServiceController::class, 'index']);
    Route::get('/services/{id}',       [ServiceController::class, 'show']);

    // Días laborales y slots (lectura para todos)
    Route::get('/working-days',                    [WorkingDayController::class, 'index']);
    Route::get('/working-days/{id}',               [WorkingDayController::class, 'show']);
    Route::get('/working-days/{id}/time-slots',    [TimeSlotController::class, 'index']);
    Route::get('/time-slots/{id}',                 [TimeSlotController::class, 'show']);

    // Historial médico (lectura para todos, filtrado por rol en el controller)
    Route::get('/medical-records',     [MedicalRecordController::class, 'index']);
    Route::get('/medical-records/{id}',[MedicalRecordController::class, 'show']);

    // Citas (lectura para todos, filtrada por rol en el controller)
    Route::get('/appointments',        [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}',   [AppointmentController::class, 'show']);
});

// ─── ADMIN (role 1) ───────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {

    // Usuarios
    Route::get('/admin/users',              [UserController::class, 'index']);
    Route::post('/admin/users',             [UserController::class, 'store']);
    Route::get('/admin/users/{id}',         [UserController::class, 'show']);
    Route::put('/admin/users/{id}',         [UserController::class, 'update']);
    Route::delete('/admin/users/{id}',      [UserController::class, 'destroy']);

    // Empleados
    Route::get('/admin/employees',          [UserController::class, 'employees']);
    Route::get('/admin/employees/{id}',     [UserController::class, 'showEmployee']);

    // Clientes (admin también puede verlos)
    Route::get('/admin/clients',            [UserController::class, 'clients']);
    Route::get('/admin/clients/{id}',       [UserController::class, 'showClient']);

    // Servicios (gestión completa)
    Route::get('/admin/services',           [ServiceController::class, 'indexAdmin']);
    Route::post('/admin/services',          [ServiceController::class, 'store']);
    Route::put('/admin/services/{id}',      [ServiceController::class, 'update']);
    Route::patch('/admin/services/{id}/toggle-active', [ServiceController::class, 'toggleActive']);
    Route::delete('/admin/services/{id}',   [ServiceController::class, 'destroy']);

    // Mascotas (gestión completa)
    Route::get('/admin/pets',               [PetController::class, 'index']);
    Route::post('/admin/pets',              [PetController::class, 'store']);
    Route::get('/admin/pets/{id}',          [PetController::class, 'show']);
    Route::put('/admin/pets/{id}',          [PetController::class, 'update']);
    Route::patch('/admin/pets/{id}/toggle-active', [PetController::class, 'toggleActive']);
    Route::delete('/admin/pets/{id}',       [PetController::class, 'destroy']);

    // Días laborales (gestión completa)
    Route::post('/admin/working-days',              [WorkingDayController::class, 'store']);
    Route::put('/admin/working-days/{id}',          [WorkingDayController::class, 'update']);
    Route::patch('/admin/working-days/{id}/cerrar', [WorkingDayController::class, 'cerrar']);
    Route::patch('/admin/working-days/{id}/abrir',  [WorkingDayController::class, 'abrir']);
    Route::delete('/admin/working-days/{id}',       [WorkingDayController::class, 'destroy']);

    // Slots (gestión completa)
    Route::post('/admin/time-slots',                        [TimeSlotController::class, 'store']);
    Route::put('/admin/time-slots/{id}',                    [TimeSlotController::class, 'update']);
    Route::patch('/admin/time-slots/{id}/cerrar',           [TimeSlotController::class, 'cerrar']);
    Route::delete('/admin/time-slots/{id}',                 [TimeSlotController::class, 'destroy']);
    Route::patch('/admin/working-days/{id}/disable-all',    [TimeSlotController::class, 'disableAllForDay']);
    Route::patch('/admin/working-days/{id}/enable-all',     [TimeSlotController::class, 'enableAllForDay']);

    // Walk-in
    Route::post('/walk-in',                 [WalkInController::class, 'store']);

    // Reporte de citas
    Route::get('/admin/appointments/reporte', [AppointmentController::class, 'reporte']);

    // Citas (gestión completa admin)
    Route::post('/admin/appointments',          [AppointmentController::class, 'store']);
    Route::put('/admin/appointments/{id}',      [AppointmentController::class, 'update']);
    Route::delete('/admin/appointments/{id}',   [AppointmentController::class, 'destroy']);
});

// ─── EMPLEADO / RECEPCIONISTA (role 2) ────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:2'])->group(function () {

    // Clientes
    Route::get('/empleado/clients',         [UserController::class, 'clients']);
    Route::get('/empleado/clients/{id}',    [UserController::class, 'showClient']);

    // Mascotas (lectura y gestión básica)
    Route::get('/empleado/pets',            [PetController::class, 'index']);
    Route::get('/empleado/pets/{id}',       [PetController::class, 'show']);
    Route::post('/empleado/pets',           [PetController::class, 'store']);
    Route::put('/empleado/pets/{id}',       [PetController::class, 'update']);

    // Walk-in
    Route::post('/walk-in',                 [WalkInController::class, 'store']);

    // Citas
    Route::post('/empleado/appointments',           [AppointmentController::class, 'store']);
    Route::put('/empleado/appointments/{id}',       [AppointmentController::class, 'update']);
    Route::delete('/empleado/appointments/{id}',    [AppointmentController::class, 'destroy']);
});

// ─── VETERINARIO (role 4) ─────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:4'])->group(function () {

    // Expedientes médicos
    Route::post('/medical-records',         [MedicalRecordController::class, 'store']);
    Route::put('/medical-records/{id}',     [MedicalRecordController::class, 'update']);

    // Mascotas (solo lectura)
    Route::get('/vet/pets',                 [PetController::class, 'index']);
    Route::get('/vet/pets/{id}',            [PetController::class, 'show']);
});

// ─── CLIENTE (role 3) ─────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:3'])->group(function () {

    // Sus mascotas
    Route::get('/mis-mascotas',             [PetController::class, 'index']);
    Route::get('/mis-mascotas/{id}',        [PetController::class, 'show']);
    Route::post('/mis-mascotas',            [PetController::class, 'store']);
    Route::put('/mis-mascotas/{id}',        [PetController::class, 'update']);

    // Sus citas
    Route::post('/cliente/appointments',            [AppointmentController::class, 'store']);
    Route::put('/cliente/appointments/{id}',        [AppointmentController::class, 'update']);
    Route::delete('/cliente/appointments/{id}',     [AppointmentController::class, 'destroy']);
});
