<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertHistory extends Model
{
    protected $table = 'alert_history';

    protected $fillable = [
        'alert_rule_id', 'channel', 'recipient',
        'status', 'message', 'error_message', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }
}