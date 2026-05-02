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

    public static function staleThresholdMinutes(): int
    {
        return (int) env('STALE_THRESHOLD_MINUTES', 3);
    }

    public function isStale(): bool
    {
        if (!$this->checked_at) return true;
        return $this->checked_at->diffInMinutes(now()) > self::staleThresholdMinutes();
    }

    public function effectiveStatus(): string
    {
        if ($this->isStale()) return 'unknown';
        return strtolower($this->status);
    }

    public static function latestPerDevice()
    {
        return self::query()
            ->select('device_status.*')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('device_status')
                  ->groupBy('device');
            })
            ->leftJoinSub(
                self::selectRaw("device, MAX(checked_at) as last_up_at")
                    ->where('status', 'up')
                    ->groupBy('device'),
                'last_up',
                'device_status.device', '=', 'last_up.device'
            )
            ->leftJoinSub(
                self::selectRaw("device, MAX(checked_at) as last_down_at")
                    ->where('status', 'down')
                    ->groupBy('device'),
                'last_down',
                'device_status.device', '=', 'last_down.device'
            )
            ->addSelect('last_up_at', 'last_down_at')
            ->orderBy('device')
            ->get();
    }

    public static function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
        }
        if ($hours > 0 || count($parts) === 0) {
            $parts[] = $hours . ' hour' . ($hours != 1 ? 's' : '');
        }
        return implode(' ', $parts);
    }

    public function isUp(): bool
    {
        return $this->effectiveStatus() === 'up';
    }

    public function badgeClass(): string
    {
        return match($this->effectiveStatus()) {
            'up'    => 'connected',
            'down'  => 'offline',
            default => 'stale',
        };
    }

    public function badgeLabel(): string
    {
        return match($this->effectiveStatus()) {
            'up'    => 'Connected',
            'down'  => 'Offline',
            default => 'Unknown',
        };
    }

    /**
     * CSS class untuk status dot di views.
     */
    public function dotClass(): string
    {
        return match($this->effectiveStatus()) {
            'up'    => 'green',
            'down'  => 'red',
            default => 'gray',
        };
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
