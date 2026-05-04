<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class IncidentController extends Controller
{
    public function index()
    {
        // ── Auto-resolve: incidents whose device is now back to normal ────────
        // Run inline so the page always reflects the latest device state
        $this->autoResolveRecovered();

        // ── Active incidents ──────────────────────────────────────────────────
        // Only incidents that are truly active (resolved_at IS NULL) AND whose
        // device actually exists in device_status (linked to real monitoring data)
        $monitoredDevices = DB::table('device_status')
            ->select('device')
            ->distinct()
            ->pluck('device');

        $activeIncidents = Incident::active()
            ->when($monitoredDevices->isNotEmpty(), function ($q) use ($monitoredDevices) {
                $q->whereIn('device', $monitoredDevices);
            })
            ->orderByRaw("FIELD(status,'Critical','Warning','Monitoring','Info')")
            ->orderBy('started_at', 'desc')
            ->get();

        // ── Stats ──────────────────────────────────────────────────────────────
        // Device Down: devices currently reported as 'down' in device_status
        $deviceDown = DB::table('device_status')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('device_status')->groupBy('device');
            })
            ->where('status', 'down')
            ->count();

        // Latency spikes: active incidents with latency-related issues
        $latencySpikes = $activeIncidents
            ->filter(fn($inc) => stripos($inc->issue, 'latency') !== false
                               || stripos($inc->issue, 'slow response') !== false)
            ->count();

        $unresolvedCount = $activeIncidents->count();

        // Success rate: percentage of resolved incidents out of all incidents
        $total    = Incident::count();
        $resolved = Incident::resolved()->count();
        $successRate = $total > 0
            ? number_format(($resolved / $total) * 100, 1)
            : '100.0';

        // ── Resolved log sidebar (5 most recent) ──────────────────────────────
        $resolvedLog = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(5)
            ->get();

        // ── Full history panel (all resolved, newest first) ───────────────────
        $fullHistory = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(200)
            ->get();

        return view('incidents', compact(
            'activeIncidents',
            'deviceDown',
            'latencySpikes',
            'unresolvedCount',
            'successRate',
            'resolvedLog',
            'fullHistory',
        ));
    }

    /**
     * Auto-resolve active incidents when their device has returned to normal.
     * Mirrors the logic in ResolveStaleIncidents artisan command so the page
     * always shows up-to-date status even without the scheduler running.
     */
    private function autoResolveRecovered(): void
    {
        $activeIncidents = Incident::whereNull('resolved_at')->get();
        if ($activeIncidents->isEmpty()) return;

        // Latest status per device (one query)
        $latestStatuses = DB::table('device_status')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('device_status')->groupBy('device');
            })
            ->get()
            ->keyBy('device');

        foreach ($activeIncidents as $incident) {
            $deviceStatus = $latestStatuses->get($incident->device);
            if (!$deviceStatus) continue; // no monitoring data → leave as-is

            $issue  = strtolower($incident->issue);
            $status = strtolower($deviceStatus->status ?? 'down');
            $isNormal = false;

            if ($this->isStatusIssue($issue)) {
                $isNormal = $status === 'up';

            } elseif ($this->isLatencyIssue($issue)) {
                if ($status === 'up') {
                    $recentAvg = DB::table('device_status')
                        ->where('device', $incident->device)
                        ->where('status', 'up')
                        ->orderByDesc('id')
                        ->limit(5)
                        ->avg('latency_ms');
                    $isNormal = ($recentAvg ?? 999) < 150;
                }

            } elseif ($this->isPacketLossIssue($issue)) {
                if ($status === 'up') {
                    $recent = DB::table('device_status')
                        ->where('device', $incident->device)
                        ->orderByDesc('id')
                        ->limit(10)
                        ->get();
                    if ($recent->isNotEmpty()) {
                        $lossRate = ($recent->where('status', 'down')->count() / $recent->count()) * 100;
                        $isNormal = $lossRate < 5;
                    }
                }

            } else {
                // Fallback: resolve if device is back up
                $isNormal = $status === 'up';
            }

            if ($isNormal) {
                $secs = $incident->started_at ? now()->diffInSeconds($incident->started_at) : 0;
                $incident->resolved_at = now();
                $incident->duration    = $this->formatDuration($secs);
                $incident->save();
            }
        }
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
        if ($seconds < 60)   return "{$seconds}s";
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
