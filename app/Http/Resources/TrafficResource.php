<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrafficResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this->resource is the array returned by TrafficService::getTrafficData()
        return [
            'bandwidth' => [
                'total_in'    => $this['totalIn'],
                'total_out'   => $this['totalOut'],
                'total_bytes' => $this['totalBytes'],
            ],
            'chart' => [
                'hours'  => $this['chartHours'],
                'values' => $this['chartValues'],
            ],
            'bandwidth_log' => $this['bandwidthLog'],
            'latency' => [
                'average_ms' => $this['avgLatency'],
                'peak_ms'    => $this['peakLatency'],
                'status'     => $this['latencyStatus'],
            ],
            'packet_loss_pct' => $this['packetLoss'],
            'top_devices'     => $this['topDevices'],
        ];
    }
}
