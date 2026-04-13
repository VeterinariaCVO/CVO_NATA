<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalkInController;
use App\Http\Controllers\WorkingDayController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        return Broadcast::auth($request);
    });

    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me', [ApiAuthController::class, 'me']);
    Route::post('/me/update', [ApiAuthController::class, 'updateProfile']);
    Route::put('/me/password', [ApiAuthController::class, 'updatePassword']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/petsi', [PetController::class, 'index']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    Route::get('/working-days', [WorkingDayController::class, 'index']);
    Route::get('/working-days/{id}', [WorkingDayController::class, 'show']);
    Route::get('/time-slots', [TimeSlotController::class, 'index']);
    Route::get('/time-slots/{id}', [TimeSlotController::class, 'show']);

    Route::get('/medical-records', [MedicalRecordController::class, 'index']);
    Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
});

// Admin role 1
Route::middleware(['auth:sanctum', 'role:1'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::get('/admin/users/{id}', [UserController::class, 'show']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);

    Route::get('/admin/employees', [UserController::class, 'employees']);
    Route::get('/admin/employees/{id}', [UserController::class, 'showEmployee']);

    Route::get('/admin/services', [ServiceController::class, 'indexAdmin']);
    Route::post('/admin/services', [ServiceController::class, 'store']);
    Route::get('/admin/services/{id}', [ServiceController::class, 'show']);
    Route::put('/admin/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/admin/services/{id}', [ServiceController::class, 'destroy']);

    Route::get('/admin/working-days', [WorkingDayController::class, 'index']);
    Route::post('/admin/working-days/generate-month', [WorkingDayController::class, 'generateMonth']);
    Route::get('/admin/working-days/{workingDay}', [WorkingDayController::class, 'show']);
    Route::patch('/admin/working-days/{workingDay}/toggle-open', [WorkingDayController::class, 'toggleOpen']);
    Route::delete('/admin/working-days/{workingDay}', [WorkingDayController::class, 'destroy']);

    Route::get('/admin/working-days/{workingDay}/time-slots', [TimeSlotController::class, 'index']);
    Route::get('/admin/time-slots/{timeSlot}', [TimeSlotController::class, 'show']);
    Route::patch('/admin/time-slots/{timeSlot}/toggle-open', [TimeSlotController::class, 'toggleOpen']);
    Route::patch('/admin/time-slots/{timeSlot}/status', [TimeSlotController::class, 'updateStatus']);
    Route::patch('/admin/working-days/{workingDay}/time-slots/disable-all', [TimeSlotController::class, 'disableAllForDay']);
    Route::patch('/admin/working-days/{workingDay}/time-slots/enable-all', [TimeSlotController::class, 'enableAllForDay']);

    Route::post('/walk-in', [WalkInController::class, 'store']);

    Route::get('/admin1/pets', [PetController::class, 'index']);
    Route::post('/admin/pets', [PetController::class, 'store']);
    Route::get('/admin1/pets/{id}', [PetController::class, 'show']);
    Route::put('/admin/pets/{id}', [PetController::class, 'update']);
    Route::delete('/admin/pets/{id}', [PetController::class, 'destroy']);
});

// Appointments for roles 1,2,4
Route::middleware(['auth:sanctum', 'role:1,2,4'])->group(function () {
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

// Receptionist role 2
Route::middleware(['auth:sanctum', 'role:2'])->group(function () {
    Route::get('/admin/pets', [PetController::class, 'index']);
    Route::get('/admin/pets/{id}', [PetController::class, 'show']);

    Route::get('/empleado/clients', [UserController::class, 'clients']);
    Route::get('/empleado/clients/{id}', [UserController::class, 'showClient']);

    Route::post('/walk-in', [WalkInController::class, 'store']);

    Route::apiResource('/pets', PetController::class);

    Route::get('/recep/appointments', [AppointmentController::class, 'index']);
    Route::get('/recep/appointments/{id}', [AppointmentController::class, 'show']);
    Route::post('/recep/appointments', [AppointmentController::class, 'store']);
    Route::put('/recep/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/recep/appointments/{id}', [AppointmentController::class, 'destroy']);
});

// Appointments for roles 1,2,3
Route::middleware(['auth:sanctum', 'role:1,2,3'])->group(function () {
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

// Appointments update for roles 1,2
Route::middleware(['auth:sanctum', 'role:1,2'])->group(function () {
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
});

// Vet role 4
Route::middleware(['auth:sanctum', 'role:4'])->group(function () {
    Route::post('/medical-records', [MedicalRecordController::class, 'store']);
    Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);

    Route::get('/pets', [PetController::class, 'index']);
    Route::get('/pets/{id}', [PetController::class, 'show']);
});

// Client role 3
Route::middleware(['auth:sanctum', 'role:3'])->group(function () {
    Route::get('/mis-mascotas', [PetController::class, 'index']);
    Route::get('/mis-mascotas/{id}', [PetController::class, 'show']);
    Route::post('/mis-mascotas', [PetController::class, 'store']);
    Route::put('/mis-mascotas/{id}', [PetController::class, 'update']);
    Route::delete('/mis-mascotas/{id}', [PetController::class, 'destroy']);

    Route::get('/client/appointments', [AppointmentController::class, 'index']);
    Route::get('/client/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/cliente/appointments/{id}', [AppointmentController::class, 'update']);
    Route::post('/cliente/appointments', [AppointmentController::class, 'store']);
    Route::delete('/cliente/appointments/{id}', [AppointmentController::class, 'destroy']);

    Route::get('/perfil', [PerfilController::class, 'show']);
    Route::post('/perfil', [PerfilController::class, 'update']);
    Route::delete('/perfil', [PerfilController::class, 'destroy']);
});



