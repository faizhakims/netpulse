<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $fillable = [
        'severity', 'title', 'description', 'channels', 'is_active',
    ];

    protected $casts = [
        'channels'  => 'array',
        'is_active' => 'boolean',
    ];

    public function history()
    {
        return $this->hasMany(AlertHistory::class, 'alert_rule_id');
    }
}
