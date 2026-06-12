<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the array returned by DashboardService::getDashboardData()
        return [
            'devices' => [
                'total'   => $this['totalDevices'],
                'up'      => $this['upDevices'],
                'down'    => $this['downDevices'],
                'unknown' => $this['unknownDevices'],
            ],
            'health_score'    => $this['healthScore'],
            'avg_latency_ms'  => $this['avgLatency'],
            'latency' => [
                'core_ms'  => $this['coreAvgLatency'],
                'edge_ms'  => $this['edgeAvgLatency'],
                'peak_ms'  => $this['peakLatency'],
            ],
            'chart' => [
                'core'   => $this['latencyCore'],
                'edge'   => $this['latencyEdge'],
            ],
            'active_incidents' => IncidentResource::collection($this['activeIncidents']),
            'max_severity'     => $this['maxSeverity'],
            'weekly_history'   => $this['weeklyChartData'],
            'monthly_history'  => $this['monthlyData'],
        ];
    }
}
