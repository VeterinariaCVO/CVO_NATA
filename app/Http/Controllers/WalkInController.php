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

    public function store(WalkInRequest $request)
    {
        $appointment = Appointment::create([
            'pet_id'     => $request->pet_id,
            'time_slot_id' => null,
            'service_id' => $request->service_id,
            'status'     => 'in_progress',
            'is_walk_in' => true,
            'notes'      => $request->notes,
            'created_by' => Auth::id(),
        ]);

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
            'Atención sin cita registrada correctamente.',
            201
        );
    }
}
