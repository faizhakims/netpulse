<?php

namespace App\Services;

class AlertEngineService
{
    /**
     * Pastikan condition kompatibel dengan metric_type.
     * - metric_type = status  → hanya is_down / is_up
     * - metric_type numerik  → hanya gt / lt / eq
     * 
     * @throws \Exception
     */
    public function validateConditionForMetric(string $metric, string $condition): void
    {
        if ($metric === 'status' && !in_array($condition, ['is_down', 'is_up'])) {
            throw new \Exception("Metric 'status' hanya mendukung kondisi 'is_down' atau 'is_up'.");
        }
        
        if ($metric !== 'status' && in_array($condition, ['is_down', 'is_up'])) {
            throw new \Exception("Kondisi 'is_down'/'is_up' hanya berlaku untuk metric 'status'.");
        }
    }
}
