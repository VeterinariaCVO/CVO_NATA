<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TimeSlot;
use Carbon\Carbon;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'time_slot_id' => 'sometimes|exists:time_slots,id',
            'pet_id'       => 'sometimes|exists:pets,id',
            'service_id'   => 'sometimes|exists:services,id',
            'notes'        => 'nullable|string|max:500',
            'status'       => 'sometimes|in:pending,confirmed,in_progress,completed,cancelled',
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
                    'Solo puedes reagendar con al menos 1 día de anticipación.'
                );
            }
        });
    }
}
