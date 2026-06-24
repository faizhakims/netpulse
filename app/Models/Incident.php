<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id', 'issue', 'status', 'duration',
        'started_at', 'resolved_at',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    protected $casts = [
        'started_at'  => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return is_null($this->resolved_at);
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            'Critical'   => 'badge-critical',
            'Warning'    => 'badge-high',
            'Monitoring' => 'badge-normal',
            default      => 'badge-info',
        };
    }

    /**
     * Hitung durasi otomatis dari started_at.
     * Jika duration sudah tersimpan, gunakan itu.
     * Guard terhadap durasi negatif (data seeder tidak konsisten).
     */
    public function displayDuration(): string
    {
        if ($this->duration) {
            return $this->duration;
        }

        if (!$this->started_at) return '—';

        $end  = $this->resolved_at ?? now();
        $secs = (int) $this->started_at->diffInSeconds($end, false); // false = signed

        // Negatif berarti started_at > end — data tidak valid, tampilkan —
        if ($secs < 0) return '—';

        if ($secs < 60)   return "{$secs}s";
        if ($secs < 3600) {
            $m = intdiv($secs, 60);
            $s = $secs % 60;
            return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
        }
        $h = intdiv($secs, 3600);
        $m = intdiv($secs % 3600, 60);
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }
}
