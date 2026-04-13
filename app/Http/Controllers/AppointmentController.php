<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmedVet;
use App\Notifications\AppointmentCreated;
use App\Notifications\AppointmentPendingAlert;
use App\Notifications\AppointmentRescheduled;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notification;

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

    /**
     * Evita enviar la misma notificación más de una vez
     * al mismo usuario para la misma cita y tipo.
     */
    private function notifyOnce($notifiable, string $type, int $appointmentId, Notification $notification): void
    {
        if (!$notifiable) {
            return;
        }

        $alreadyExists = $notifiable->notifications()
            ->where('data->type', $type)
            ->where('data->appointment_id', $appointmentId)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $notifiable->notify($notification);
    }

    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = $this->baseQuery();

        if ($user->isCliente()) {
            $query->whereHas('pet', fn($q) => $q->where('owner_id', $user->id));
        }

        if ($user->isVeterinario()) {
            $query->whereIn('status', ['confirmed', 'in_progress', 'completed']);
        }

        if ($request->filled('pet_id')) {
            $query->where('pet_id', $request->pet_id);
        }

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

        $appointment->load([
            'pet.owner',
            'timeSlot.workingDay',
            'service',
            'creator',
        ]);

        $owner = $appointment->pet?->owner;
        $creator = Auth::user();

        // Notificar al dueño
        if ($owner) {
            $this->notifyOnce(
                $owner,
                'appointment_created',
                $appointment->id,
                new AppointmentCreated($appointment)
            );
        }

        // Notificar al creador si no es el mismo dueño
        if ($creator && (!$owner || $creator->id !== $owner->id)) {
            $this->notifyOnce(
                $creator,
                'appointment_created',
                $appointment->id,
                new AppointmentCreated($appointment)
            );
        }

        // Notificar a recepcionistas y admin que hay una cita pendiente de confirmar
        User::whereIn('role_id', [1, 2])
            ->where('active', true)
            ->get()
            ->each(function ($user) use ($appointment) {
                $this->notifyOnce(
                    $user,
                    'appointment_pending_alert',
                    $appointment->id,
                    new AppointmentPendingAlert($appointment)
                );
            });

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
        $appointment = Appointment::with(['pet.owner', 'timeSlot.workingDay', 'service', 'creator'])->findOrFail($id);
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

            $newSlot->update([
                'status'  => 'reserved',
                'is_open' => false,
            ]);

            $wasRescheduled = true;
        }

        $appointment->update([
            'pet_id'       => $request->pet_id ?? $appointment->pet_id,
            'time_slot_id' => $request->time_slot_id ?? $appointment->time_slot_id,
            'service_id'   => $request->service_id ?? $appointment->service_id,
            'notes'        => $request->notes ?? $appointment->notes,
            'status'       => $request->status ?? $appointment->status,
        ]);

        $appointment->load([
            'pet.owner',
            'timeSlot.workingDay',
            'service',
            'creator',
        ]);

        $owner = $appointment->pet?->owner;

        if ($wasRescheduled) {
            if ($owner) {
                $this->notifyOnce(
                    $owner,
                    'appointment_rescheduled',
                    $appointment->id,
                    new AppointmentRescheduled($appointment)
                );
            }

            User::whereIn('role_id', [1, 2])
                ->where('active', true)
                ->get()
                ->each(function ($adminOrReception) use ($appointment) {
                    $this->notifyOnce(
                        $adminOrReception,
                        'appointment_rescheduled',
                        $appointment->id,
                        new AppointmentRescheduled($appointment)
                    );
                });
        }

        if ($request->filled('status') && $request->status !== $oldStatus) {
            if ($owner) {
                $this->notifyOnce(
                    $owner,
                    'appointment_status_changed',
                    $appointment->id,
                    new AppointmentStatusChanged($appointment)
                );
            }

            if ($request->status === 'confirmed') {
                User::where('role_id', 4)
                    ->where('active', true)
                    ->get()
                    ->each(function ($vet) use ($appointment) {
                        $this->notifyOnce(
                            $vet,
                            'appointment_confirmed_vet',
                            $appointment->id,
                            new AppointmentConfirmedVet($appointment)
                        );
                    });
            }

            if ($request->status === 'cancelled') {
                User::whereIn('role_id', [1, 2])
                    ->where('active', true)
                    ->get()
                    ->each(function ($adminOrReception) use ($appointment) {
                        $this->notifyOnce(
                            $adminOrReception,
                            'appointment_cancelled_alert',
                            $appointment->id,
                            new AppointmentCancelledAlert($appointment, Auth::user())
                        );
                    });
            }
        }

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
        $appointment->load([
            'pet.owner',
            'timeSlot.workingDay',
            'service',
            'creator',
        ]);

        $owner = $appointment->pet?->owner;

        // Notificar al dueño
        if ($owner) {
            $this->notifyOnce(
                $owner,
                'appointment_cancelled',
                $appointment->id,
                new AppointmentCancelled($appointment)
            );
        }

        // Notificar a recepcionistas y admin que se canceló una cita
        User::whereIn('role_id', [1, 2])
            ->where('active', true)
            ->get()
            ->each(function ($adminOrReception) use ($appointment, $user) {
                $this->notifyOnce(
                    $adminOrReception,
                    'appointment_cancelled_alert',
                    $appointment->id,
                    new AppointmentCancelled($appointment)
                );
            });

        return $this->success(null, 'Cita cancelada correctamente');
    }
}
