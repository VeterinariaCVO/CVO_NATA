<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'appointment_id' => $this->appointment_id,
            'veterinarian'   => [
                'id'   => $this->veterinarian?->id,
                'name' => $this->veterinarian?->name,
            ],
            'pet'            => $this->appointment?->pet ? [
                'id'      => $this->appointment->pet->id,
                'name'    => $this->appointment->pet->name,
                'species' => $this->appointment->pet->species,
            ] : null,
            'service'        => $this->appointment?->service?->name,
            'date'           => $this->appointment?->timeSlot?->workingDay?->date
                                ?? $this->created_at?->format('Y-m-d'),
            'weight'         => $this->weight,
            'temperature'    => $this->temperature,
            'symptoms'       => $this->symptoms,
            'diagnosis'      => $this->diagnosis,
            'treatment'      => $this->treatment,
            'prescriptions'  => $this->prescriptions,
            'observations'   => $this->observations,
            'next_visit'     => $this->next_visit?->format('Y-m-d'),
            'created_at'     => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
