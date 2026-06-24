<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'layer',
        'type',
    ];

    public function statuses()
    {
        return $this->hasMany(DeviceStatus::class);
    }

    public function traffic()
    {
        return $this->hasMany(InterfaceTraffic::class);
    }

    public function metrics()
    {
        return $this->hasMany(SnmpMetric::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function alertRules()
    {
        return $this->hasMany(AlertRule::class, 'target_device_id');
    }
}
