<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceStatus extends Model
{
    protected $table = 'device_status';
    public $timestamps = false;

    protected $fillable = [
        'device', 'ip_address', 'status', 'latency_ms', 'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'latency_ms' => 'float',
    ];

    /**
     * Ambil status terakhir setiap device (distinct by device name).
     */
    public static function latestPerDevice()
    {
        return self::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('device_status')
                  ->groupBy('device');
            })
            ->orderBy('device')
            ->get();
    }

    public function isUp(): bool
    {
        return strtolower($this->status) === 'up';
    }

    public function badgeClass(): string
    {
        return $this->isUp() ? 'connected' : 'offline';
    }

    public function badgeLabel(): string
    {
        return $this->isUp() ? 'Connected' : 'Offline';
    }

    /**
     * Gambar generik berdasarkan nama device.
     */
    public function imageUrl(): string
    {
        $name = strtolower($this->device);
        if (str_contains($name, 'switch')) return asset('images/switch.png');
        return asset('images/router.png');
    }
}
