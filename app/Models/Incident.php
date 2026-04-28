<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'device', 'ip_address', 'issue', 'status', 'duration',
        'started_at', 'resolved_at',
    ];

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
     * Hitung durasi otomatis dari started_at jika kolom duration kosong.
     */
    public function displayDuration(): string
    {
        if ($this->duration) {
            return $this->duration;
        }

        $end = $this->resolved_at ?? now();
        $diff = $this->started_at->diff($end);

        if ($diff->h > 0) {
            return "{$diff->h}h {$diff->i}m";
        }
        if ($diff->i > 0) {
            return "{$diff->i}m {$diff->s}s";
        }
        return "{$diff->s}s";
    }
}
