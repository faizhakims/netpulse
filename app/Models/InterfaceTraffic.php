<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceTraffic extends Model
{
    protected $table = 'interface_traffic';
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'interface_name',
        'bytes_in', 'bytes_out', 'packets_in', 'packets_out', 'collected_at',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    protected $casts = [
        'collected_at' => 'datetime',
        'bytes_in'     => 'integer',
        'bytes_out'    => 'integer',
        'packets_in'   => 'integer',
        'packets_out'  => 'integer',
    ];

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_000_000_000_000) return round($bytes / 1_000_000_000_000, 2) . ' TB';
        if ($bytes >= 1_000_000_000)     return round($bytes / 1_000_000_000, 2) . ' GB';
        if ($bytes >= 1_000_000)         return round($bytes / 1_000_000, 2) . ' MB';
        if ($bytes >= 1_000)             return round($bytes / 1_000, 2) . ' KB';
        return $bytes . ' B';
    }

    public static function latestPerInterface()
    {
        return self::query()
            ->with('device')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('interface_traffic')
                  ->groupBy('device_id', 'interface_name');
            })
            ->orderByDesc('bytes_in')
            ->get();
    }

    public function formattedBytesIn(): string
    {
        return self::formatBytes($this->bytes_in ?? 0);
    }

    public function formattedBytesOut(): string
    {
        return self::formatBytes($this->bytes_out ?? 0);
    }
}
