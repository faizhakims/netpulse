<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Ringkasan Device ──────────────────────────────────────────────
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
        $mainRouterStatus = $latestDevices->firstWhere('device', $mainRouterName);

        // Core latency: hanya valid kalau device UP dan tidak stale
        $coreAvgLatency = ($mainRouterStatus && $mainRouterStatus->effectiveStatus() === 'up')
            ? round($mainRouterStatus->latency_ms, 2)
            : null;

        $edgeDevices    = $latestDevices->where('device', '!=', $mainRouterName)
            ->filter(fn($d) => $d->effectiveStatus() === 'up');
        $edgeAvgLatency = $edgeDevices->count() > 0
            ? round($edgeDevices->avg('latency_ms'), 2)
            : null;

        $peakLatency = $latestDevices
            ->filter(fn($d) => $d->effectiveStatus() === 'up')
            ->max('latency_ms') ?? 0;

        // ── Data grafik latency 21 titik ───────────────────────────────────
        $staleThreshold = DeviceStatus::staleThresholdMinutes();

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

        foreach ($checkTimes as $time) {
            $coreVal = DB::table('device_status')
                ->where('device', $mainRouterName)
                ->where('checked_at', $time)
                ->value('latency_ms');
            $latencyCore[] = min(200, round($coreVal ?? 0));

            $edgeAvgVal = DB::table('device_status')
                ->where('device', '!=', $mainRouterName)
                ->where('checked_at', $time)
                ->avg('latency_ms');
            $latencyEdge[] = min(200, round($edgeAvgVal ?? 0));
        }

        while (count($latencyCore) < 21) $latencyCore[] = 0;
        while (count($latencyEdge) < 21) $latencyEdge[] = 0;

        // ── Active Incidents ───────────────────────────────────────────────
        // Only show incidents for devices that exist in device_status
        // (same logic as IncidentController to keep both pages in sync)
        $monitoredDevices = DB::table('device_status')
            ->select('device')->distinct()->pluck('device');

        $activeIncidents = \App\Models\Incident::whereNull('resolved_at')
            ->when($monitoredDevices->isNotEmpty(), fn($q) => $q->whereIn('device', $monitoredDevices))
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
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rows = DB::table('device_status')
                ->whereDate('checked_at', $date->toDateString())
                ->get();
            $total = $rows->count();
            $up    = $rows->where('status', 'up')->count();
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $weeklyChartData[] = [
                'label' => $date->format('D'),
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        // ── Performance History (Monthly) ──────────────────────────────────
        $monthlyData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rows = DB::table('device_status')
                ->whereDate('checked_at', $date->toDateString())
                ->get();
            $total = $rows->count();
            $up    = $rows->where('status', 'up')->count();
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $monthlyData[] = [
                'label' => $date->format('M d'),
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        return view('dashboard', compact(
            'totalDevices', 'upDevices', 'downDevices', 'unknownDevices', 'avgLatency',
            'healthScore', 'latestDevices',
            'activeIncidents', 'maxSeverity',
            'latencyCore', 'latencyEdge',
            'coreAvgLatency', 'edgeAvgLatency', 'peakLatency',
            'weeklyChartData', 'monthlyData',
            'allDevices'
        ));
    }

    // ── Ekspor CSV ────────────────────────────────────────────────────────
    public function exportCsv()
    {
        abort_unless(auth()->user()->can('view dashboard'), 403, 'Access denied.');
        $devices = DeviceStatus::latestPerDevice();

        $filename = 'device_inventory_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($devices) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Node Identity', 'IP Address', 'Layer', 'Last Checked', 'Effective Status']);

            foreach ($devices as $device) {
                fputcsv($handle, [
                    $device->device,
                    $device->ip_address,
                    'Network Device',
                    $device->checked_at ? $device->checked_at->diffForHumans() : '-',
                    strtoupper($device->effectiveStatus()),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
