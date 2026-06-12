<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'severity', 'title', 'description', 'channels', 'is_active',
        'target_device', 'metric_type', 'condition', 'threshold_value',
        'duration', 'trigger_count', 'last_triggered_at', 'sort_order',
    ];

    protected $casts = [
        'channels'          => 'array',
        'is_active'         => 'boolean',
        'threshold_value'   => 'float',
        'trigger_count'     => 'integer',
        'last_triggered_at' => 'datetime',
        'sort_order'        => 'integer',
    ];

    public function history()
    {
        return $this->hasMany(AlertHistory::class, 'alert_rule_id');
    }

    public function conditionLabel(): string
    {
        // Untuk metric status, tidak perlu threshold value
        if ($this->metric_type === 'status') {
            if ($this->condition === 'is_down') {
                return "If no 'up' received for " . ($this->duration ?? '5m') . " (sustained down)";
            }
            return "If Status is UP for " . ($this->duration ?? '5m');
        }

        $map = ['gt' => '>', 'lt' => '<', 'eq' => '='];
        $metricUnit = match ($this->metric_type ?? 'latency') {
            'latency'     => 'ms',
            'bandwidth'   => 'Mbps',
            'packet_loss' => '%',
            default       => '',
        };
        $cond   = $map[$this->condition ?? 'gt'] ?? '>';
        $metric = ucfirst(str_replace('_', ' ', $this->metric_type ?? 'latency'));
        return "If {$metric} {$cond} {$this->threshold_value}{$metricUnit} for " . ($this->duration ?? '5m');
    }
}
