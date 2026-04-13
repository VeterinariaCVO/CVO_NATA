<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentCancelledAlert;
use App\Notifications\AppointmentConfirmedVet;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentPendingAlert;
use App\Notifications\AppointmentRescheduled;
use App\Notifications\AppointmentStatusChanged;
use App\Notifications\NewAppointmentAssigned;
use App\Models\User;
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

    public function index()
    {
        $user  = Auth::user();
        $query = $this->baseQuery();

        if ($user->isCliente()) {
            $query->whereHas('pet', fn($q) => $q->where('owner_id', $user->id));
        }

        if ($user->isVeterinario()) {
            $query->whereIn('status', ['confirmed', 'in_progress', 'completed']);
        }

        if (request('status')) {
            $query->where('status', request('status'));
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

    $owner   = $appointment->pet->owner;
    $creator = Auth::user();

    // Notificar al dueño siempre
    if ($owner) {
        $owner->notify(new AppointmentCreated($appointment));
    }

    // Notificar a todos los veterinarios activos
    User::where('role_id', 4)
        ->where('active', true)
        ->get()
        ->each(fn($vet) => $vet->notify(new NewAppointmentAssigned($appointment)));

    // Notificar a admins y recepcionistas EXCEPTO al creador
    User::whereIn('role_id', [1, 2])
        ->where('active', true)
        ->where('id', '!=', $creator->id)
        ->get()
        ->each(fn($u) => $u->notify(new AppointmentPendingAlert($appointment)));

    return $this->success(
        new AppointmentResource($appointment),
        'Cita registrada correctamente',
        201
    );
}

public function update(UpdateAppointmentRequest $request, $id)
{
    $appointment = Appointment::findOrFail($id);
    $oldStatus   = $appointment->status;
    $wasRescheduled = false;

    if ($request->filled('time_slot_id') && $request->time_slot_id != $appointment->time_slot_id) {
        $newSlot = TimeSlot::findOrFail($request->time_slot_id);

        if ($newSlot->status === 'reserved') {
            return $this->error('El horario seleccionado está ocupado.', 400);
        }

        TimeSlot::find($appointment->time_slot_id)?->update(['status' => 'available']);
        $newSlot->update(['status' => 'reserved']);
        $wasRescheduled = true;
    }

    $appointment->update([
        'pet_id'       => $request->pet_id       ?? $appointment->pet_id,
        'time_slot_id' => $request->time_slot_id ?? $appointment->time_slot_id,
        'service_id'   => $request->service_id   ?? $appointment->service_id,
        'notes'        => $request->notes        ?? $appointment->notes,
        'status'       => $request->status       ?? $appointment->status,
    ]);

    $appointment->load(['pet.owner', 'timeSlot.workingDay', 'service']);
    $owner = $appointment->pet?->owner;

    // Reagendamiento — notificar a dueño y staff excepto al que reagendó
    if ($wasRescheduled) {
        if ($owner) {
            $owner->notify(new AppointmentRescheduled($appointment));
        }

        User::whereIn('role_id', [1, 2])
            ->where('active', true)
            ->where('id', '!=', Auth::id())
            ->get()
            ->each(fn($u) => $u->notify(new AppointmentRescheduled($appointment)));
    }

    // Cambio de estado
    if ($request->filled('status') && $request->status !== $oldStatus) {

        if ($owner) {
            $owner->notify(new AppointmentStatusChanged($appointment));
        }

        if ($request->status === 'confirmed') {
            User::where('role_id', 4)
                ->where('active', true)
                ->get()
                ->each(fn($vet) => $vet->notify(new AppointmentConfirmedVet($appointment)));
        }

        if ($request->status === 'cancelled') {
            User::whereIn('role_id', [1, 2])
                ->where('active', true)
                ->where('id', '!=', Auth::id()) // ← excluir al que canceló
                ->get()
                ->each(fn($u) => $u->notify(new AppointmentCancelledAlert($appointment, Auth::user())));
        }
    }

    return $this->success(
        new AppointmentResource($appointment->fresh(['pet.owner', 'timeSlot.workingDay', 'service', 'creator'])),
        'Cita actualizada correctamente'
    );
}

public function destroy($id)
{
    $appointment = $this->baseQuery()->findOrFail($id);

    if (!$appointment->isCancellable()) {
        return $this->error('Esta cita no puede cancelarse en su estado actual.', 422);
    }

    TimeSlot::find($appointment->time_slot_id)?->update(['status' => 'available']);
    $appointment->update(['status' => 'cancelled']);

    $owner = $appointment->pet?->owner;

    // Notificar al dueño
    if ($owner) {
        $owner->notify(new AppointmentCancelled($appointment));
    }

    // Notificar al staff excepto al que canceló
    User::whereIn('role_id', [1, 2])
        ->where('active', true)
        ->where('id', '!=', Auth::id()) // ← excluir al que canceló
        ->get()
        ->each(fn($u) => $u->notify(new AppointmentCancelledAlert($appointment, Auth::user())));

    return $this->success(null, 'Cita cancelada correctamente');
}
}
