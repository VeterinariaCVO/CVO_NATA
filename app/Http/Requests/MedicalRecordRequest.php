<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class MedicalRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        if ($isUpdate) {
            $appointmentRule = 'sometimes|exists:appointments,id';
        } else {
            $appointmentRule = 'required|exists:appointments,id|unique:medical_records,appointment_id';
        }

        return [
            'appointment_id' => $appointmentRule,
            'weight'         => 'nullable|numeric|min:0',
            'temperature'    => 'nullable|numeric|min:30|max:45',
            'symptoms'       => 'nullable|string|max:1000',
            'diagnosis'      => 'nullable|string|max:1000',
            'treatment'      => 'nullable|string|max:1000',
            'prescriptions'  => 'nullable|string|max:1000',
            'observations'   => 'nullable|string|max:1000',
            'next_visit'     => 'nullable|date|after:today',
        ];
    }
}
