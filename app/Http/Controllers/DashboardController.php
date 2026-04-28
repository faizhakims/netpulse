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

        $totalDevices  = $latestDevices->count();
        $upDevices     = $latestDevices->where('status', 'up')->count();
        $downDevices   = $totalDevices - $upDevices;
        $avgLatency    = round($latestDevices->avg('latency_ms'), 2);

        $healthScore   = $totalDevices > 0
            ? round(($upDevices / $totalDevices) * 100)
            : 0;

        // ── Identitas main-router dari config ─────────────────────────────
        $mainRouterName = config('netpulse.main_router', 'main-router');

        // ── Statistik latency (Core, Edge, Peak) ──────────────────────────
        $mainRouterStatus = $latestDevices->firstWhere('device', $mainRouterName);
        $coreAvgLatency = $mainRouterStatus ? round($mainRouterStatus->latency_ms, 2) : $avgLatency;

        $edgeDevices    = $latestDevices->where('device', '!=', $mainRouterName);
        $edgeAvgLatency = $edgeDevices->count() > 0
            ? round($edgeDevices->avg('latency_ms'), 2)
            : 0;

        $peakLatency = $latestDevices->max('latency_ms') ?? 0;

        // ── Data grafik latency 21 titik (berdasarkan checked_at unik) ──
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

        // Isi jika kurang dari 21 titik (fallback)
        while (count($latencyCore) < 21) {
            $latencyCore[] = $coreAvgLatency;
        }
        while (count($latencyEdge) < 21) {
            $latencyEdge[] = $edgeAvgLatency;
        }

        // ── Active Incidents (fallback, jika model Incident belum ada) ──
        // Jika sudah membuat Incident model, ganti dengan query yang sesuai
        $activeIncidents = collect(); // Incident::where('status', 'active')->with('device')->get();
        $maxSeverity = $activeIncidents->max('severity') ?? 'NONE';

        // ── Device untuk slide panel "View All Managed Nodes" ────────────
        $allDevices = DeviceStatus::latestPerDevice();

        // ── Performance History (Weekly) ─────────────────────────────────
        $weeklyChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayLabel = $date->format('D');
            $rows = DB::table('device_status')
                ->whereDate('checked_at', $date->toDateString())
                ->get();
            $total = $rows->count();
            $up    = $rows->where('status', 'up')->count();
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $weeklyChartData[] = [
                'label' => $dayLabel,
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        // ── Performance History (Monthly) ────────────────────────────────
        $monthlyData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayLabel = $date->format('M d');
            $rows = DB::table('device_status')
                ->whereDate('checked_at', $date->toDateString())
                ->get();
            $total = $rows->count();
            $up    = $rows->where('status', 'up')->count();
            $pct   = $total > 0 ? round(($up / $total) * 100) : 0;
            $type  = $pct >= 80 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
            $monthlyData[] = [
                'label' => $dayLabel,
                'h'     => $pct . '%',
                'type'  => $type,
                'pct'   => $pct,
            ];
        }

        return view('dashboard', compact(
            'totalDevices', 'upDevices', 'downDevices', 'avgLatency',
            'healthScore', 'latestDevices',
            'activeIncidents', 'maxSeverity',
            'latencyCore', 'latencyEdge',
            'coreAvgLatency', 'edgeAvgLatency', 'peakLatency',
            'weeklyChartData', 'monthlyData',
            'allDevices'
        ));
    }

    // ── Ekspor CSV ───────────────────────────────────────────────────────
    public function exportCsv()
    {
        $devices = DeviceStatus::latestPerDevice();

        $filename = 'device_inventory_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($devices) {
            $handle = fopen('php://output', 'w');
            // BOM untuk Excel agar terbaca UTF-8 dengan benar
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Node Identity', 'IP Address', 'Layer', 'Uptime', 'Status']);

            foreach ($devices as $device) {
                fputcsv($handle, [
                    $device->device,
                    $device->ip_address,
                    'Network Device',
                    $device->checked_at ? $device->checked_at->diffForHumans() : '-',
                    strtoupper($device->status),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}