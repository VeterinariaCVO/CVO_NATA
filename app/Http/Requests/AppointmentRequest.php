<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TimeSlot;
use Carbon\Carbon;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pet_id'       => 'required|exists:pets,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'service_id'   => 'required|exists:services,id',
            'status'       => 'nullable|in:pending,confirmed,arrived,in_progress,completed,cancelled',
            'notes'        => 'nullable|string|max:500',
            'vet_id'       => 'nullable|exists:users,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $slotId = $this->input('time_slot_id');
            if (!$slotId) return;

            $slot = TimeSlot::with('workingDay')->find($slotId);
            if (!$slot || !$slot->workingDay) return;

            $slotDate = Carbon::parse($slot->workingDay->date)->startOfDay();
            $today    = Carbon::today();

            if ($slotDate->lte($today)) {
                $validator->errors()->add(
                    'time_slot_id',
                    'La cita debe agendarse con al menos 1 día de anticipación.'
                );
            }
        });
    }
}
