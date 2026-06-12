<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'device'      => $this->device,
            'ip_address'  => $this->ip_address,
            'issue'       => $this->issue,
            'status'      => $this->status,
            'is_active'   => $this->isActive(),
            'duration'    => $this->displayDuration(),
            'started_at'  => $this->started_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
