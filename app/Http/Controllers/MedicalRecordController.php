<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Notifications\AppointmentCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = MedicalRecord::with([
            'appointment.pet.owner',
            'appointment.service',
            'appointment.timeSlot.workingDay',
            'veterinarian',
        ]);

        // Filtro por mascota (cualquier rol que lo mande)
        if ($request->filled('pet_id')) {
            $query->whereHas('appointment', fn($q) =>
            $q->where('pet_id', $request->integer('pet_id'))
            );
        }

        // Cliente: solo sus propias mascotas
        if ($user->isCliente()) {
            $query->whereHas('appointment.pet', fn($q) =>
            $q->where('owner_id', $user->id)
            );
        }

        // Veterinario: solo sus expedientes
        if ($user->isVeterinario()) {
            $query->where('veterinarian_id', $user->id);
        }

        return $this->success(
            MedicalRecordResource::collection(
                $query->orderByDesc('created_at')->get()
            )
        );
    }

    public function store(MedicalRecordRequest $request)
    {
        if (MedicalRecord::where('appointment_id', $request->appointment_id)->exists()) {
            return $this->error('Esta consulta ya tiene un registro clínico guardado.', 422);
        }

        $appointment = Appointment::with(['pet.owner', 'service'])->findOrFail($request->appointment_id);

        $estadosPermitidos = ['confirmed', 'in_progress', 'arrived'];

        if (!in_array($appointment->status, $estadosPermitidos)) {
            return $this->error('Solo se puede registrar un expediente para citas en sala, confirmadas o en curso.', 422);
        }

        return DB::transaction(function () use ($request, $appointment) {
            $record = MedicalRecord::create(array_merge(
                $request->validated(),
                ['veterinarian_id' => Auth::id()]
            ));

            $appointment->update(['status' => 'completed']);

            $owner = $appointment->pet?->owner;
            if ($owner) {
                $owner->notify(new AppointmentCompleted($appointment));
            }

            return $this->success(
                new MedicalRecordResource($record->load([
                    'veterinarian',
                    'appointment.pet',
                    'appointment.service',
                    'appointment.timeSlot.workingDay',
                ])),
                'Expediente médico registrado correctamente y consulta finalizada.',
                201
            );
        });
    }

    public function show($id) { }
    public function update(MedicalRecordRequest $request, $id) { }
}
