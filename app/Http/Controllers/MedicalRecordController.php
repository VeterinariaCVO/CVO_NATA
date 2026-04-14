<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\MedicalRecord;
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

        // Cliente solo ve los expedientes de sus mascotas
        if ($user->isCliente()) {
            $query->whereHas('appointment.pet', fn($q) => $q->where('owner_id', $user->id));
        }

        // Veterinario solo ve los expedientes que él creó
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

        if (!in_array($appointment->status, ['confirmed', 'in_progress'])) {
            return $this->error('Solo se puede registrar un expediente para citas confirmadas o en curso.', 422);
        }

        // Al insertar el registro, el trigger trg_completar_cita
        // cambia el status de la cita a 'completed' automáticamente.
        // No necesitamos hacer $appointment->update(['status' => 'completed']) aquí.
        $record = MedicalRecord::create(array_merge(
            $request->validated(),
            ['veterinarian_id' => Auth::id()]
        ));

        return $this->success(
            new MedicalRecordResource($record->load([
                'veterinarian',
                'appointment.pet',
                'appointment.service',
                'appointment.timeSlot.workingDay',
            ])),
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

        $data = $request->validated();
        unset($data['appointment_id']); // el appointment no cambia al editar

        $record->update($data);

        return $this->success(
            new MedicalRecordResource($record->load([
                'veterinarian',
                'appointment.pet',
                'appointment.service',
            ])),
            'Expediente actualizado correctamente'
        );
    }
}
