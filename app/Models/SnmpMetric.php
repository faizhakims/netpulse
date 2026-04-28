<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnmpMetric extends Model
{
    protected $table = 'snmp_metrics';
    public $timestamps = false;

    protected $fillable = [
        'device', 'ip_address', 'metric_name', 'metric_value', 'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Ambil semua metric terbaru per device+metric_name.
     */
    public static function latestPerDeviceMetric()
    {
        return self::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device', 'metric_name');
            })
            ->orderBy('device')
            ->get();
    }

    /**
     * Ambil metric tertentu untuk semua device.
     */
    public static function latestByMetricName(string $metricName)
    {
        return self::query()
            ->where('metric_name', $metricName)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device', 'metric_name');
            })
            ->get();
    }
}
