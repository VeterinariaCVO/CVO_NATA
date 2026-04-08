<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalkInRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\WalkInCreated;
use Illuminate\Support\Facades\Auth;

class WalkInController extends Controller
{
    use ApiResponse;

    // CU-20: Registrar atención sin cita.
    // Actor: Empleado (role 2) o Admin (role 1).
    // Entra directo en status = in_progress.

    public function store(WalkInRequest $request)
    {
        // Si viene un slot, verificar que esté disponible
        if ($request->filled('time_slot_id')) {
            $slot = TimeSlot::findOrFail($request->time_slot_id);

            if ($slot->status === 'reserved') {
                return $this->error('El bloque seleccionado ya está ocupado.', 400);
            }

            $slot->update(['status' => 'reserved']);
        }

        $appointment = Appointment::create([
            'pet_id'       => $request->pet_id,
            'time_slot_id' => $request->time_slot_id ?? null,
            'service_id'   => $request->service_id,
            'status'       => 'in_progress',
            'is_walk_in'   => true,
            'notes'        => $request->notes,
            'created_by'   => Auth::id(),
        ]);

        $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service', 'creator']);

        // Notificar a todos los veterinarios activos
        User::veterinarios()->each(
    fn($vet) => $vet->notify(new WalkInCreated($appointment))
);

        // Notificar al dueño de la mascota
        $owner = $appointment->pet?->owner;
        if ($owner) {
            $owner->notify(new AppointmentCreated($appointment));
        }

        return $this->success(
            new AppointmentResource($appointment),
            'Atención sin cita registrada correctamente.',
            201
        );
    }
}
