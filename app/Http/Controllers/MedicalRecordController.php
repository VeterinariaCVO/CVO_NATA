<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Support\Facades\Auth;

class MedicalRecordController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $user  = Auth::user();
        $query = MedicalRecord::with([
            'appointment.pet.owner',
            'appointment.service',
            'appointment.timeSlot.workingDay',
            'veterinarian',
        ]);

        // Cliente: solo historial de sus mascotas
        if ($user->isCliente()) {
            $query->whereHas('appointment.pet', fn($q) => $q->where('owner_id', $user->id));
        }

        // Veterinario: solo sus registros
        if ($user->isVeterinario()) {
            $query->where('veterinarian_id', $user->id);
        }

        return $this->success(
            MedicalRecordResource::collection($query->orderByDesc('created_at')->get())
        );
    }

    public function store(MedicalRecordRequest $request)
    {
        $appointment = Appointment::with(['pet.owner', 'service'])->findOrFail($request->appointment_id);

        // Solo se puede crear un expediente si la cita está en curso o confirmada
        if (!in_array($appointment->status, ['confirmed', 'in_progress'])) {
            return $this->error('Solo se puede registrar un expediente para citas confirmadas o en curso.', 422);
        }

        $record = MedicalRecord::create(array_merge(
            $request->validated(),
            ['veterinarian_id' => Auth::id()]
        ));

        $appointment->update(['status' => 'completed']);

        $owner = $appointment->pet?->owner;
        if ($owner) {
            $owner->notify(new AppointmentStatusChanged($appointment));
        }

        return $this->success(
            new MedicalRecordResource($record->load(['veterinarian', 'appointment.pet', 'appointment.service', 'appointment.timeSlot.workingDay'])),
            'Expediente médico registrado correctamente',
            201
        );
    }


    public function show($id)
    {
        $record = MedicalRecord::with([
            'appointment.pet.owner',
            'appointment.service',
            'appointment.timeSlot.workingDay',
            'veterinarian',
        ])->findOrFail($id);

        return $this->success(new MedicalRecordResource($record));
    }

    public function update(MedicalRecordRequest $request, $id)
    {
        $record = MedicalRecord::findOrFail($id);

        // No permitir cambiar el appointment_id en un update
        $data = $request->validated();
        unset($data['appointment_id']);

        $record->update($data);

        return $this->success(
            new MedicalRecordResource($record->load(['veterinarian', 'appointment.pet', 'appointment.service'])),
            'Expediente actualizado correctamente'
        );
    }
}
