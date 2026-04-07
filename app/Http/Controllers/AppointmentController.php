<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    use ApiResponse;

    private function baseQuery()
    {
        return Appointment::with([
            'pet.owner',
            'timeSlot.workingDay',
            'service',
            'creator',
        ]);
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = $this->baseQuery();

        if ($user->isCliente()) {
            $query->whereHas('pet', fn($q) => $q->where('owner_id', $user->id));
        }

        // Veterinario: solo citas en estados relevantes
        if ($user->isVeterinario()) {
            $query->whereIn('status', ['confirmed', 'in_progress', 'completed']);
        }

        // FIX: filtrar por mascota si se manda pet_id
        if ($request->filled('pet_id')) {
            $query->where('pet_id', $request->pet_id);
        }

        // FIX: filtrar por status (soporta array: status[]=confirmed&status[]=in_progress)
        if ($request->filled('status')) {
            $statuses = is_array($request->status)
                ? $request->status
                : explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        return $this->success(
            AppointmentResource::collection($query->orderByDesc('created_at')->get())
        );
    }

    public function store(AppointmentRequest $request)
    {
        $slot = TimeSlot::findOrFail($request->time_slot_id);

        if ($slot->status === 'reserved') {
            return $this->error('El horario seleccionado ya no está disponible.', 400);
        }

        $appointment = Appointment::create([
            'pet_id'       => $request->pet_id,
            'time_slot_id' => $request->time_slot_id,
            'service_id'   => $request->service_id,
            'status'       => 'pending',
            'is_walk_in'   => false,
            'notes'        => $request->notes,
            'created_by'   => Auth::id(),
        ]);

        $slot->update(['status' => 'reserved']);

        $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service', 'creator']);

        $owner = $appointment->pet->owner;
        if ($owner) {
            $owner->notify(new AppointmentCreated($appointment));
        }

        $creator = Auth::user();
        if ($creator && $owner && $creator->id !== $owner->id) {
            $creator->notify(new AppointmentCreated($appointment));
        }

        return $this->success(
            new AppointmentResource($appointment),
            'Cita registrada correctamente',
            201
        );
    }

    public function show($id)
    {
        $appointment = $this->baseQuery()->findOrFail($id);
        return $this->success(new AppointmentResource($appointment));
    }

    public function update(UpdateAppointmentRequest $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $oldStatus   = $appointment->status;

        if ($request->time_slot_id && $request->time_slot_id !== $appointment->time_slot_id) {
            $newSlot = TimeSlot::findOrFail($request->time_slot_id);

            if ($newSlot->status === 'reserved') {
                return $this->error('El horario seleccionado está ocupado.', 400);
            }

            if ($appointment->time_slot_id) {
                TimeSlot::find($appointment->time_slot_id)?->update(['status' => 'available']);
            }

            $newSlot->update(['status' => 'reserved']);
        }

        $appointment->update([
            'pet_id'       => $request->pet_id       ?? $appointment->pet_id,
            'time_slot_id' => $request->time_slot_id ?? $appointment->time_slot_id,
            'service_id'   => $request->service_id   ?? $appointment->service_id,
            'notes'        => $request->notes        ?? $appointment->notes,
            'status'       => $request->status       ?? $appointment->status,
        ]);

        if ($request->filled('status') && $request->status !== $oldStatus) {
            $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service']);
            $owner = $appointment->pet?->owner;
            if ($owner) {
                $owner->notify(new AppointmentStatusChanged($appointment));
            }
        }

        $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service', 'creator']);

        return $this->success(
            new AppointmentResource($appointment),
            'Cita actualizada correctamente'
        );
    }

    public function destroy($id)
    {
        $appointment = $this->baseQuery()->findOrFail($id);

        if (!$appointment->isCancellable()) {
            return $this->error('Esta cita no puede cancelarse en su estado actual.', 422);
        }

        if ($appointment->time_slot_id) {
            TimeSlot::find($appointment->time_slot_id)?->update(['status' => 'available']);
        }

        $appointment->update(['status' => 'cancelled']);

        $owner = $appointment->pet?->owner;
        if ($owner) {
            $owner->notify(new AppointmentCancelled($appointment));
        }

        return $this->success(null, 'Cita cancelada correctamente');
    }
}