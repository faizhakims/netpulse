<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'severity'         => $this->severity,
            'metric_type'      => $this->metric_type,
            'condition'        => $this->condition,
            'threshold_value'  => $this->threshold_value,
            'duration'         => $this->duration,
            'target_device'    => $this->targetDevice->name ?? null,
            'channels'         => $this->channels,
            'is_active'        => $this->is_active,
            'trigger_count'    => $this->trigger_count,
            'last_triggered_at'=> $this->last_triggered_at?->toISOString(),
            'sort_order'       => $this->sort_order,
            'condition_label'  => $this->conditionLabel(),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
