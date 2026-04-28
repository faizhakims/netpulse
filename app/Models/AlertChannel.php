<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertChannel extends Model
{
    protected $fillable = ['type', 'is_active', 'config'];

    protected $casts = [
        'is_active' => 'boolean',
        'config'    => 'array',
    ];
}
