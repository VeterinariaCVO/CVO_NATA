<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'working_day_id' => $this->working_day_id,
            'date'           => $this->workingDay?->date?->format('Y-m-d'),
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'status'         => $this->status,
        ];
    }
}
