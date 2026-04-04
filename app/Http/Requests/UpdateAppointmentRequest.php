<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'time_slot_id' => 'nullable|exists:time_slots,id',
            'pet_id'       => 'sometimes|exists:pets,id',
            'service_id'   => 'sometimes|exists:services,id',
            'notes'        => 'nullable|string|max:500',
            'status'       => 'sometimes|in:pending,confirmed,in_progress,completed,cancelled',
        ];
    }
}
