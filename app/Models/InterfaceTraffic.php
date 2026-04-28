<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceTraffic extends Model
{
    protected $table = 'interface_traffic';
    public $timestamps = false;

    protected $fillable = [
        'device', 'ip_address', 'interface_name',
        'bytes_in', 'bytes_out', 'packets_in', 'packets_out', 'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'bytes_in'     => 'integer',
        'bytes_out'    => 'integer',
        'packets_in'   => 'integer',
        'packets_out'  => 'integer',
    ];

    /**
     * Convert bytes ke format human-readable (KB/MB/GB/TB).
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_000_000_000_000) return round($bytes / 1_000_000_000_000, 2) . ' TB';
        if ($bytes >= 1_000_000_000)     return round($bytes / 1_000_000_000, 2) . ' GB';
        if ($bytes >= 1_000_000)         return round($bytes / 1_000_000, 2) . ' MB';
        if ($bytes >= 1_000)             return round($bytes / 1_000, 2) . ' KB';
        return $bytes . ' B';
    }

    /**
     * Ambil record terbaru per device+interface.
     */
    public static function latestPerInterface()
    {
        return self::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('interface_traffic')
                  ->groupBy('device', 'interface_name');
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
