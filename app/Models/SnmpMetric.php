<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnmpMetric extends Model
{
    protected $table = 'snmp_metrics';
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'metric_name', 'metric_value', 'collected_at',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Ambil semua metric terbaru per device+metric_name.
     */
    public static function latestPerDeviceMetric()
    {
        return self::query()
            ->with('device')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device_id', 'metric_name');
            })
            ->get()
            ->sortBy(fn($m) => $m->device->name ?? '');
    }

    /**
     * Ambil metric tertentu untuk semua device.
     */
    public static function latestByMetricName(string $metricName)
    {
        return self::query()
            ->with('device')
            ->where('metric_name', $metricName)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device_id', 'metric_name');
            })
            ->get();
    }
}
