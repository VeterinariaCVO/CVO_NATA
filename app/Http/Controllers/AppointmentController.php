<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\VwCita;
use App\Models\VwReporteCita;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentCancelledAlert;
use App\Notifications\AppointmentConfirmedVet;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentPendingAlert;
use App\Notifications\AppointmentRescheduled;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewAppointmentAssigned;
use App\Models\User;

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
        $query = VwCita::query();

        if ($user->isCliente()) {
            $query->where('cliente_id', $user->id);
        }

        if ($user->isVeterinario()) {
            $query->whereIn('status', ['confirmed', 'in_progress', 'completed']);
        }

        if ($request->filled('pet_id')) {
            $query->where('mascota_id', $request->pet_id);
        }

        if ($request->filled('status')) {
            $statuses = is_array($request->status)
                ? $request->status
                : explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        return $this->success($query->orderByDesc('created_at')->get());
    }

    public function reporte(Request $request)
    {
        $query = VwReporteCita::query();

        if ($request->filled('fecha_inicio')) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }

        return $this->success($query->orderByDesc('fecha')->get());
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

        if ($owner) {
            $owner->notify(new AppointmentCreated($appointment));
        }

        if ($creator && $owner && $creator->id !== $owner->id) {
            $creator->notify(new AppointmentCreated($appointment));
        }

        User::veterinarios()->each(
            fn($vet) => $vet->notify(new NewAppointmentAssigned($appointment))
        );

        User::whereIn('role_id', [1, 2])->where('active', true)->get()
            ->each(fn($u) => $u->notify(new AppointmentPendingAlert($appointment)));

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
        $user        = Auth::user();

        if ($user->isCliente() && $appointment->pet->owner_id !== $user->id) {
            return $this->error('No autorizado.', 403);
        }

        $wasRescheduled = false;

        if ($request->filled('time_slot_id') && $request->time_slot_id != $appointment->time_slot_id) {
            $newSlot = TimeSlot::findOrFail($request->time_slot_id);

            if ($newSlot->status === 'reserved') {
                return $this->error('El horario seleccionado está ocupado.', 400);
            }

            if ($appointment->time_slot_id) {
                TimeSlot::find($appointment->time_slot_id)?->update([
                    'status'  => 'available',
                    'is_open' => true,
                ]);
            }

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

        if ($wasRescheduled) {
            if ($owner) {
                $owner->notify(new AppointmentRescheduled($appointment));
            }
            User::whereIn('role_id', [1, 2])->where('active', true)->get()
                ->each(fn($u) => $u->notify(new AppointmentRescheduled($appointment)));
        }

        if ($request->filled('status') && $request->status !== $oldStatus) {

            if ($request->status === 'cancelled' && $appointment->time_slot_id) {
                TimeSlot::find($appointment->time_slot_id)?->update([
                    'status'  => 'available',
                    'is_open' => true,
                ]);
            }

            if ($owner) {
                $owner->notify(new AppointmentStatusChanged($appointment));
            }

            if ($request->status === 'confirmed') {
                User::where('role_id', 4)->where('active', true)->get()
                    ->each(fn($vet) => $vet->notify(new AppointmentConfirmedVet($appointment)));
            }

            if ($request->status === 'cancelled') {
                User::whereIn('role_id', [1, 2])->where('active', true)->get()
                    ->each(fn($u) => $u->notify(new AppointmentCancelledAlert($appointment, Auth::user())));
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
        $user        = Auth::user();

        if ($user->isCliente() && $appointment->pet->owner_id !== $user->id) {
            return $this->error('No autorizado.', 403);
        }

        if (!$appointment->isCancellable()) {
            return $this->error('Esta cita no puede cancelarse en su estado actual.', 422);
        }

        if ($appointment->time_slot_id) {
            TimeSlot::find($appointment->time_slot_id)?->update([
                'status'  => 'available',
                'is_open' => true,
            ]);
        }

        $appointment->update(['status' => 'cancelled']);

        $owner = $appointment->pet?->owner;
        if ($owner) {
            $owner->notify(new AppointmentCancelled($appointment));
        }

        User::whereIn('role_id', [1, 2])->where('active', true)->get()
            ->each(fn($u) => $u->notify(new AppointmentCancelledAlert($appointment, Auth::user())));

        return $this->success(null, 'Cita cancelada correctamente');
    }
}
