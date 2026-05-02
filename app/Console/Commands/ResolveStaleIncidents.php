<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

/**
 * ResolveStaleIncidents
 *
 * Dijalankan setiap menit (lihat routes/console.php).
 * Logika:
 *   - Ambil semua incident yang masih active (resolved_at IS NULL).
 *   - Cek kondisi device saat ini dari device_status (record terbaru per device).
 *   - Jika kondisi kembali normal sesuai jenis issue, set resolved_at = now().
 *
 * Kondisi "normal" per jenis issue:
 *   - "Device Down" / status-based  → device status = 'up'
 *   - "Latency*"                    → device status = 'up' DAN latency_ms di bawah threshold
 *   - "Packet Loss*"                → rate packet loss (10 record terakhir) < 5%
 *   - Lainnya                       → device status = 'up' (fallback aman)
 */
class ResolveStaleIncidents extends Command
{
    protected $signature   = 'incidents:resolve {--debug : Tampilkan detail evaluasi}';
    protected $description = 'Auto-resolve active incidents ketika device kembali ke kondisi normal.';

    /** Latency dianggap normal jika di bawah nilai ini (ms) */
    const LATENCY_NORMAL_MS = 150;

    /** Packet loss dianggap normal jika di bawah nilai ini (%) */
    const PACKET_LOSS_NORMAL_PCT = 5;

    public function handle(): void
    {
        $debug = $this->option('debug');

        // Ambil semua incident active
        $activeIncidents = Incident::whereNull('resolved_at')->get();

        if ($activeIncidents->isEmpty()) {
            if ($debug) $this->info('Tidak ada incident aktif.');
            return;
        }

        if ($debug) $this->info("Mengecek {$activeIncidents->count()} incident aktif...");

        // Ambil status terbaru per device sekaligus (1 query)
        $latestStatuses = DB::table('device_status')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('device_status')->groupBy('device');
            })
            ->get()
            ->keyBy('device');

        $resolvedCount = 0;

        foreach ($activeIncidents as $incident) {
            $deviceStatus = $latestStatuses->get($incident->device);

            if (!$deviceStatus) {
                if ($debug) $this->line("  [{$incident->device}] Tidak ada data status, skip.");
                continue;
            }

            $isNormal = $this->checkIfNormal($incident, $deviceStatus, $debug);

            if ($isNormal) {
                $incident->resolved_at = now();
                // Hitung durasi aktual dan simpan
                $incident->duration = $this->formatDuration(
                    $incident->started_at
                        ? now()->diffInSeconds($incident->started_at)
                        : 0
                );
                $incident->save();
                $resolvedCount++;
                $this->line("  ✅ Resolved: INC-" . str_pad($incident->id, 4, '0', STR_PAD_LEFT)
                    . " [{$incident->device}] — {$incident->issue}");
            } else {
                if ($debug) $this->line("  ⏳ Still active: [{$incident->device}] — {$incident->issue}");
            }
        }

        if ($resolvedCount > 0) {
            $this->info("{$resolvedCount} incident(s) auto-resolved — " . now()->format('H:i:s'));
        } elseif ($debug) {
            $this->info('Tidak ada incident yang bisa di-resolve saat ini.');
        }
    }

    /**
     * Tentukan apakah incident sudah bisa di-resolve berdasarkan kondisi device saat ini.
     */
    private function checkIfNormal(Incident $incident, object $deviceStatus, bool $debug): bool
    {
        $issue  = strtolower($incident->issue);
        $status = strtolower($deviceStatus->status ?? 'down');
        $latency = (float) ($deviceStatus->latency_ms ?? 0);

        // ── Device Down / Connection Lost / Unreachable ───────────────────────
        if ($this->isStatusIssue($issue)) {
            $normal = $status === 'up';
            if ($debug) $this->line("    [status check] device_status={$status} → " . ($normal ? 'NORMAL' : 'still down'));
            return $normal;
        }

        // ── Latency Spike / High Latency ─────────────────────────────────────
        if ($this->isLatencyIssue($issue)) {
            // Device harus UP dulu
            if ($status !== 'up') {
                if ($debug) $this->line("    [latency check] device DOWN, belum bisa resolve.");
                return false;
            }
            // Ambil rata-rata latency 5 record terakhir agar tidak spike sesaat
            $recentAvg = DB::table('device_status')
                ->where('device', $incident->device)
                ->where('status', 'up')
                ->orderByDesc('id')
                ->limit(5)
                ->avg('latency_ms');

            $checkLatency = $recentAvg ?? $latency;
            $normal = $checkLatency < self::LATENCY_NORMAL_MS;
            if ($debug) $this->line("    [latency check] avg={$checkLatency}ms < " . self::LATENCY_NORMAL_MS . "ms → " . ($normal ? 'NORMAL' : 'still high'));
            return $normal;
        }

        // ── Packet Loss ───────────────────────────────────────────────────────
        if ($this->isPacketLossIssue($issue)) {
            if ($status !== 'up') return false;
            $recent = DB::table('device_status')
                ->where('device', $incident->device)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
            if ($recent->isEmpty()) return false;
            $lossRate = ($recent->where('status', 'down')->count() / $recent->count()) * 100;
            $normal = $lossRate < self::PACKET_LOSS_NORMAL_PCT;
            if ($debug) $this->line("    [packet_loss check] loss={$lossRate}% < " . self::PACKET_LOSS_NORMAL_PCT . "% → " . ($normal ? 'NORMAL' : 'still high'));
            return $normal;
        }

        // ── Interface Flapping ────────────────────────────────────────────────
        if (str_contains($issue, 'flapping') || str_contains($issue, 'interface')) {
            // Resolve jika device UP dan stabil (tidak ada fluktuasi status dalam 5 record terakhir)
            if ($status !== 'up') return false;
            $recent = DB::table('device_status')
                ->where('device', $incident->device)
                ->orderByDesc('id')
                ->limit(5)
                ->pluck('status');
            $allUp = $recent->every(fn($s) => strtolower($s) === 'up');
            if ($debug) $this->line("    [flapping check] allUp={$allUp} → " . ($allUp ? 'NORMAL' : 'still flapping'));
            return $allUp;
        }

        // ── Fallback: resolusi jika device UP ────────────────────────────────
        $normal = $status === 'up';
        if ($debug) $this->line("    [fallback check] device_status={$status} → " . ($normal ? 'NORMAL' : 'still issue'));
        return $normal;
    }

    private function isStatusIssue(string $issue): bool
    {
        return str_contains($issue, 'down')
            || str_contains($issue, 'unreachable')
            || str_contains($issue, 'connection lost')
            || str_contains($issue, 'offline');
    }

    private function isLatencyIssue(string $issue): bool
    {
        return str_contains($issue, 'latency')
            || str_contains($issue, 'slow response')
            || str_contains($issue, 'high response');
    }

    private function isPacketLossIssue(string $issue): bool
    {
        return str_contains($issue, 'packet loss')
            || str_contains($issue, 'packet drop');
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60)  return "{$seconds}s";
        if ($seconds < 3600) {
            $m = intdiv($seconds, 60);
            $s = $seconds % 60;
            return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }
}