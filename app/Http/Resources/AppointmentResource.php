<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $owner = $this->pet?->owner;

        return [
            'id'          => $this->id,
            'is_walk_in'  => $this->is_walk_in,
            'status'      => $this->status,
            'notes'       => $this->notes,
            'pet'         => [
                'id'   => $this->pet?->id,
                'name' => $this->pet?->name,
            ],
            'client'      => $owner ? [
                'id'    => $owner->id,
                'name'  => $owner->name,
                'phone' => $owner->phone,
            ] : null,
            'service'     => $this->service ? [
                'id'   => $this->service->id,
                'name' => $this->service->name,
                'price'=> $this->service->price,
            ] : null,
            'time_slot'   => $this->timeSlot ? [
                'id'         => $this->timeSlot->id,
                'date'       => $this->timeSlot->workingDay?->date,
                'start_time' => $this->timeSlot->start_time,
                'end_time'   => $this->timeSlot->end_time,
            ] : null,
            'created_by'  => $this->creator?->name,
            'created_at'  => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
