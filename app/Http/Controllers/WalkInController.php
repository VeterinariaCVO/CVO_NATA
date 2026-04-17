<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalkInRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCreated;
use App\Notifications\WalkInRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WalkInController extends Controller
{
    use ApiResponse;

    /**
     * Registrar atención sin cita (walk-in)
     */
    public function store(WalkInRequest $request)
    {
        try {
            // Crear la cita walk-in
            $appointment = Appointment::create([
                'pet_id'       => $request->pet_id,
                'time_slot_id' => null,
                'service_id'   => $request->service_id,
                'status'       => 'in_progress',
                'is_walk_in'   => true,
                'notes'        => $request->notes,
                'created_by'   => Auth::id(),
            ]);

            // Recargar relaciones completas (IMPORTANTE para notificaciones)
            $appointment = Appointment::with([
                'pet.owner',
                'timeSlot.workingDay',
                'service',
                'creator',
            ])->findOrFail($appointment->id);

            // Notificar veterinarios activos
            $vets = User::where('role_id', 4)
                ->where('active', true)
                ->get();

            foreach ($vets as $vet) {
                $vet->notify(new WalkInRegistered($appointment));
            }

            // Notificar admins activos
            $admins = User::where('role_id', 1)
                ->where('active', true)
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new WalkInRegistered($appointment));
            }

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
        } catch (\Throwable $e) {
            Log::error('Error al registrar walk-in', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la atención.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }
}