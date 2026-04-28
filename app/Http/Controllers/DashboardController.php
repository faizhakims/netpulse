<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Models\InterfaceTraffic;
use App\Models\SnmpMetric;
use Illuminate\Support\Facades\DB;

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

        // Health score sederhana berdasarkan % device up
        $healthScore   = $totalDevices > 0
            ? round(($upDevices / $totalDevices) * 100)
            : 0;

        // ── Traffic Summary ───────────────────────────────────────────────
        $trafficSummary = DB::table('interface_traffic')
            ->selectRaw('SUM(bytes_in) as total_in, SUM(bytes_out) as total_out')
            ->first();

        $totalBytesIn  = $trafficSummary->total_in  ?? 0;
        $totalBytesOut = $trafficSummary->total_out ?? 0;

        // ── Top Busiest Devices (by bytes_in) ────────────────────────────
        $topDevices = DB::table('interface_traffic')
            ->selectRaw('device, ip_address, SUM(bytes_in) as total_in, SUM(bytes_out) as total_out')
            ->groupBy('device', 'ip_address')
            ->orderByDesc('total_in')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->bandwidth_mbps = round(($row->total_in + $row->total_out) / 1_000_000, 1);
                return $row;
            });

        // ── SNMP Metrics ringkasan ────────────────────────────────────────
        $snmpMetrics = SnmpMetric::latestPerDeviceMetric()
            ->groupBy('device');

        return view('dashboard', compact(
            'totalDevices', 'upDevices', 'downDevices', 'avgLatency',
            'healthScore', 'totalBytesIn', 'totalBytesOut',
            'topDevices', 'snmpMetrics', 'latestDevices'
        ));
    }
}
