<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceStatus extends Model
{
    protected $table = 'device_status';
    public $timestamps = false;

    protected $fillable = [
        'device_id', 'status', 'latency_ms', 'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'latency_ms' => 'float',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public static function staleThresholdMinutes(): int
    {
        return (int) config('netpulse.stale_threshold_minutes', 3);
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
            ->with('device')
            ->select('device_status.*')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('device_status')
                  ->groupBy('device_id');
            })
            ->leftJoinSub(
                self::selectRaw("device_id, MAX(checked_at) as last_up_at")
                    ->where('status', 'up')
                    ->groupBy('device_id'),
                'last_up',
                'device_status.device_id', '=', 'last_up.device_id'
            )
            ->leftJoinSub(
                self::selectRaw("device_id, MAX(checked_at) as last_down_at")
                    ->where('status', 'down')
                    ->groupBy('device_id'),
                'last_down',
                'device_status.device_id', '=', 'last_down.device_id'
            )
            ->addSelect('last_up_at', 'last_down_at')
            ->get()
            ->sortBy(fn($status) => $status->device->name ?? '');
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
        $name = strtolower($this->device->name ?? '');
        if (str_contains($name, 'switch')) return asset('images/switch.png');
        return asset('images/router.png');
    }
}
