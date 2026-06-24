<?php

namespace App\Services;

use App\Models\DeviceStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData(): array
    {
        $latestDevices = DeviceStatus::latestPerDevice();
        $totalDevices = $latestDevices->count();

        // Hitung berdasarkan effectiveStatus() — stale device TIDAK dihitung UP
        $upDevices      = $latestDevices->filter(fn($d) => $d->effectiveStatus() === 'up')->count();
        $downDevices    = $latestDevices->filter(fn($d) => $d->effectiveStatus() === 'down')->count();
        $unknownDevices = $latestDevices->filter(fn($d) => $d->effectiveStatus() === 'unknown')->count();

        // Latency hanya dari device yang benar-benar UP (bukan stale)
        $activeLatencies = $latestDevices
            ->filter(fn($d) => $d->effectiveStatus() === 'up')
            ->pluck('latency_ms')
            ->filter();
        $avgLatency = $activeLatencies->count() ? round($activeLatencies->avg(), 2) : 0;

        // Health score: unknown dihitung setengah, down = 0
        $healthScore = $totalDevices > 0
            ? round((($upDevices + ($unknownDevices * 0)) / $totalDevices) * 100)
            : 0;

        // ── Identitas main-router dari config ─────────────────────────────
        $mainRouterName = config('netpulse.main_router', 'main-router');

        // ── Statistik latency (Core, Edge, Peak) ──────────────────────────
        $mainRouterStatus = $latestDevices->firstWhere(fn($d) => ($d->device->name ?? '') === $mainRouterName);

        // Core latency: hanya valid kalau device UP dan tidak stale
        $coreAvgLatency = ($mainRouterStatus && $mainRouterStatus->effectiveStatus() === 'up')
            ? round($mainRouterStatus->latency_ms, 2)
            : null;

        $edgeDevices    = $latestDevices->filter(fn($d) => ($d->device->name ?? '') !== $mainRouterName)
            ->filter(fn($d) => $d->effectiveStatus() === 'up');
        $edgeAvgLatency = $edgeDevices->count() > 0
            ? round($edgeDevices->avg('latency_ms'), 2)
            : null;

        $peakLatency = $latestDevices
            ->filter(fn($d) => $d->effectiveStatus() === 'up')
            ->max('latency_ms') ?? 0;

        // ── Data grafik latency 21 titik ───────────────────────────────────
        $checkTimes = DB::table('device_status')
            ->select('checked_at')
            ->distinct()
            ->orderByDesc('checked_at')
            ->limit(21)
            ->pluck('checked_at')
            ->reverse()
            ->values();

        $latencyCore = [];
        $latencyEdge = [];

        $latencies = DB::table('device_status')
            ->join('devices', 'device_status.device_id', '=', 'devices.id')
            ->whereIn('checked_at', $checkTimes)
            ->select('checked_at', 'devices.name as device_name', 'latency_ms')
            ->get()
            ->groupBy('checked_at');

        foreach ($checkTimes as $time) {
            $records = $latencies->get($time, collect());
            
            $coreRecord = $records->firstWhere('device_name', $mainRouterName);
            $coreVal = $coreRecord ? $coreRecord->latency_ms : 0;
            $latencyCore[] = min(200, round($coreVal));

            $edgeRecords = $records->where('device_name', '!=', $mainRouterName);
            $edgeAvgVal = $edgeRecords->isEmpty() ? 0 : $edgeRecords->avg('latency_ms');
            $latencyEdge[] = min(200, round($edgeAvgVal));
        }

        while (count($latencyCore) < 21) $latencyCore[] = 0;
        while (count($latencyEdge) < 21) $latencyEdge[] = 0;

        // ── Active Incidents ───────────────────────────────────────────────
        $monitoredDeviceIds = DB::table('device_status')
            ->select('device_id')->distinct()->pluck('device_id');

        $activeIncidents = \App\Models\Incident::whereNull('resolved_at')
            ->with('device')
            ->when($monitoredDeviceIds->isNotEmpty(), fn($q) => $q->whereIn('device_id', $monitoredDeviceIds))
            ->orderByRaw("FIELD(status,'Critical','Warning','Monitoring','Info')")
            ->orderByDesc('started_at')
            ->get();

        // Severity priority: Critical > Warning > Monitoring > Info
        $severityOrder = ['Critical' => 4, 'Warning' => 3, 'Monitoring' => 2, 'Info' => 1];
        $maxSeverity = $activeIncidents->sortByDesc(fn($i) => $severityOrder[$i->status] ?? 0)
            ->first()?->status ?? 'NONE';

        // ── Semua device untuk slide panel ────────────────────────────────
        $allDevices = $latestDevices;

        // ── Performance History (Weekly) ───────────────────────────────────
        $weeklyChartData = [];
        
        $startWeekly = Carbon::today()->subDays(6)->toDateString();
        $endWeekly = Carbon::today()->toDateString();
        
        $weeklyStats = DB::table('device_status')
            ->whereDate('checked_at', '>=', $startWeekly)
            ->whereDate('checked_at', '<=', $endWeekly)
            ->select(DB::raw('DATE(checked_at) as date'), 'status', DB::raw('count(*) as count'))
            ->groupBy('date', 'status')
            ->get();

        for ($i = 6; $i >= 0; $i--) {
            $dateStr = Carbon::today()->subDays($i)->toDateString();
            $dayStats = $weeklyStats->where('date', $dateStr);
            $total = $dayStats->sum('count');
            $up = $dayStats->where('status', 'up')->sum('count');
            
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $weeklyChartData[] = [
                'label' => Carbon::parse($dateStr)->format('D'),
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        // ── Performance History (Monthly) ──────────────────────────────────
        $monthlyData = [];

        $startMonthly = Carbon::today()->subDays(29)->toDateString();
        $endMonthly = Carbon::today()->toDateString();
        
        $monthlyStats = DB::table('device_status')
            ->whereDate('checked_at', '>=', $startMonthly)
            ->whereDate('checked_at', '<=', $endMonthly)
            ->select(DB::raw('DATE(checked_at) as date'), 'status', DB::raw('count(*) as count'))
            ->groupBy('date', 'status')
            ->get();

        for ($i = 29; $i >= 0; $i--) {
            $dateStr = Carbon::today()->subDays($i)->toDateString();
            $dayStats = $monthlyStats->where('date', $dateStr);
            $total = $dayStats->sum('count');
            $up = $dayStats->where('status', 'up')->sum('count');
            
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $monthlyData[] = [
                'label' => Carbon::parse($dateStr)->format('M d'),
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        return compact(
            'totalDevices', 'upDevices', 'downDevices', 'unknownDevices', 'avgLatency',
            'healthScore', 'latestDevices',
            'activeIncidents', 'maxSeverity',
            'latencyCore', 'latencyEdge',
            'coreAvgLatency', 'edgeAvgLatency', 'peakLatency',
            'weeklyChartData', 'monthlyData',
            'allDevices'
        );
    }
}
