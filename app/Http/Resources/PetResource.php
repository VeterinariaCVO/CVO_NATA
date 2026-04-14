<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'species'       => $this->species,
            'breed'         => $this->breed,
            'color'         => $this->color,
            'special_marks' => $this->special_marks,
            'weight'        => $this->weight,
            'sex'           => $this->sex,
            'age'           => $this->age,
            'photo_url'     => $this->photo_path
                                ? asset('storage/' . $this->photo_path)
                                : null,
            'owner_id'      => $this->owner_id,
            'owner'         => $this->whenLoaded('owner', fn() => [
                'id'    => $this->owner->id,
                'name'  => $this->owner->name,
                'email' => $this->owner->email,
                'phone' => $this->owner->phone,
            ]),
            'active'        => $this->active,
            'created_at'    => $this->created_at?->format('Y-m-d'),
        ];
    }
}
