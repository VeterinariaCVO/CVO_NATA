<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date'       => $this->date?->format('Y-m-d'),
            'is_open'    => $this->is_open,
            'time_slots' => TimeSlotResource::collection($this->whenLoaded('timeSlots')),
        ];
    }
}
