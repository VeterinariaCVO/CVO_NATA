<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Notifications\AppointmentCompleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if ($user->isCliente()) {
            $query->whereHas('appointment.pet', fn($q) => $q->where('owner_id', $user->id));
        }

        if ($user->isVeterinario()) {
            $query->where('veterinarian_id', $user->id);
        }

        return $this->success(
            MedicalRecordResource::collection($query->orderByDesc('created_at')->get())
        );
    }

    public function store(MedicalRecordRequest $request)
    {
        // 1. Verificar si ya existe un expediente para esta cita (evita duplicados)
        if (MedicalRecord::where('appointment_id', $request->appointment_id)->exists()) {
            return $this->error('Esta consulta ya tiene un registro clínico guardado.', 422);
        }

        $appointment = Appointment::with(['pet.owner', 'service'])->findOrFail($request->appointment_id);

        // 2. AGREGADO 'arrived': Permitir crear expediente si el paciente está en sala, confirmado o en curso
        $estadosPermitidos = ['confirmed', 'in_progress', 'arrived'];

        if (!in_array($appointment->status, $estadosPermitidos)) {
            return $this->error('Solo se puede registrar un expediente para citas en sala, confirmadas o en curso.', 422);
        }

        return DB::transaction(function () use ($request, $appointment) {
            // 3. Crear el registro
            $record = MedicalRecord::create(array_merge(
                $request->validated(),
                ['veterinarian_id' => Auth::id()]
            ));

            // 4. Finalizar la cita
            $appointment->update(['status' => 'completed']);

            // 5. Notificar al dueño
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

    // Los métodos show y update están perfectos, no requieren cambios.
    public function show($id) { /* ... */ }
    public function update(MedicalRecordRequest $request, $id) { /* ... */ }
}
