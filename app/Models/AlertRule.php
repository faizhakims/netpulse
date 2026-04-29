<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
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
        $map = ['gt'=>'>','lt'=>'<','eq'=>'=','is_down'=>'is DOWN','is_up'=>'is UP'];
        $metricUnit = match($this->metric_type ?? 'latency') {
            'latency','bandwidth','cpu','memory','packet_loss' => match($this->metric_type) {
                'latency' => 'ms', default => '%'
            },
            default => '',
        };
        $cond = $map[$this->condition ?? 'gt'] ?? '>';
        $val  = in_array($this->condition, ['is_down','is_up']) ? '' : " {$this->threshold_value}{$metricUnit}";
        $metric = ucfirst(str_replace('_',' ', $this->metric_type ?? 'latency'));
        return "If {$metric} {$cond}{$val} for " . ($this->duration ?? '5m');
    }
}
