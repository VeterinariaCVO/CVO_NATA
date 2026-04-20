<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalkInRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\WalkInRegistered;
use Illuminate\Support\Facades\Auth;

class WalkInController extends Controller
{
    use ApiResponse;

  public function store(\Illuminate\Http\Request $request)
{
    // Usamos Request directo para evitar que tu WalkInRequest bloquee las variables nuevas

    // 1. MANEJO INTELIGENTE DEL PACIENTE
    if ($request->is_emergency) {
        // A) Emergencia: Creamos un cliente "Exprés"
        $owner = User::create([
            'name'     => $request->owner_name,
            'phone'    => $request->phone,
            'email'    => 'urgencia_' . time() . '@cvo.com', // Correo temporal único
            'password' => bcrypt('12345678'), // Contraseña genérica
            'role_id'  => 3,
            'active'   => true,
        ]);

        // B) Emergencia: Creamos a la mascota "Exprés" y la enlazamos
        $pet = \App\Models\Pet::create([
            'name'     => $request->pet_name,
            'species'  => $request->species ?? 'Otro',
            'owner_id' => $owner->id,
        ]);

        $petId = $pet->id;
    } else {
        // Paciente Registrado normal
        $petId = $request->pet_id;
    }

    // 2. CREACIÓN DE LA CITA
    $appointment = Appointment::create([
        'pet_id'       => $petId,
        'time_slot_id' => null,
        'service_id'   => $request->service_id,
        'vet_id'       => $request->vet_id,    // ¡Doctor Asignado!
        'status'       => 'arrived',           // ¡Nace "En Sala" para que el Doc lo vea!
        'is_walk_in'   => true,
        'notes'        => $request->notes,
        'created_by'   => Auth::id(),
    ]);

    // 3. CARGAR RELACIONES Y NOTIFICAR
    $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service', 'creator']);

    User::where('role_id', 4)->where('active', true)->get()
        ->each(fn($vet) => $vet->notify(new WalkInRegistered($appointment)));

    User::where('role_id', 1)->where('active', true)->get()
        ->each(fn($admin) => $admin->notify(new WalkInRegistered($appointment)));

    $owner = $appointment->pet?->owner;
    if ($owner) {
        $owner->notify(new AppointmentCreated($appointment));
    }

    return $this->success(
        new AppointmentResource($appointment),
        'Atención de urgencia registrada y enviada a sala.',
        201
    );
}
}
