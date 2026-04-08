<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class WalkInRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pet_id'       => 'required|exists:pets,id',
            'service_id'   => 'required|exists:services,id',
            'time_slot_id' => 'nullable|exists:time_slots,id',
            'notes'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'pet_id.required'    => 'Debes seleccionar una mascota.',
            'service_id.required'=> 'Debes seleccionar un servicio.',
        ];
    }
}
