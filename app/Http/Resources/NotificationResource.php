<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->data['type']    ?? null,
            'title'      => $this->data['title']   ?? null,
            'message'    => $this->data['message'] ?? null,
            'data'       => $this->data,
            'read'       => !is_null($this->read_at),
            'read_at'    => $this->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
