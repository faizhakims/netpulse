<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'            => $this->device->name ?? $this->device,
            'ip_address'      => $this->device->ip_address ?? $this->ip_address,
            'status'          => $this->effectiveStatus(),
            'raw_status'      => strtolower($this->status),
            'is_stale'        => $this->isStale(),
            'latency_ms'      => $this->latency_ms,
            'last_up_at'      => $this->last_up_at   ?? null,
            'last_down_at'    => $this->last_down_at ?? null,
            'checked_at'      => $this->checked_at?->toISOString(),
        ];
    }
}
