<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Models\InterfaceTraffic;
use App\Models\SnmpMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = DeviceStatus::latestPerDevice();
        return view('device', compact('devices'));
    }

    public function show(string $deviceName)
    {
        // Status terbaru
        $status = DeviceStatus::where('device', $deviceName)
            ->latest('checked_at')
            ->first();

        // Riwayat latency (50 terakhir) untuk chart
        $latencyHistory = DeviceStatus::where('device', $deviceName)
            ->whereNotNull('latency_ms')
            ->orderByDesc('checked_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $latencyLabels = $latencyHistory->map(fn($r) =>
            $r->checked_at ? $r->checked_at->format('H:i') : ''
        );
        $latencyData = $latencyHistory->pluck('latency_ms');

        $latencyAvg  = $latencyData->count() ? round($latencyData->avg(), 1) : null;
        $latencyPeak = $latencyData->count() ? round($latencyData->max(), 1) : null;
        $latencyMin  = $latencyData->count() ? round($latencyData->min(), 1) : null;

        // Riwayat status (20 terakhir) untuk log activity
        $statusHistory = DeviceStatus::where('device', $deviceName)
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get();

        // Traffic interfaces
        $traffic = InterfaceTraffic::where('device', $deviceName)
            ->latest('collected_at')
            ->get();

        // SNMP metrics
        $metrics = SnmpMetric::where('device', $deviceName)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device', 'metric_name');
            })
            ->get()
            ->keyBy('metric_name');

        // Hitung uptime % dari statusHistory
        $allStatuses = DeviceStatus::where('device', $deviceName)->get();
        $upCount   = $allStatuses->where('status', 'up')->count();
        $total     = $allStatuses->count();
        $uptimePct = $total > 0 ? round(($upCount / $total) * 100, 2) : 100;

        return view('details', compact(
            'status', 'statusHistory', 'traffic', 'metrics', 'deviceName',
            'latencyLabels', 'latencyData', 'latencyAvg', 'latencyPeak', 'latencyMin',
            'uptimePct'
        ));
    }

    public function ping(Request $request)
    {
        $request->validate([
            'device' => 'required|string'
        ]);

        try {
            $response = Http::timeout(5)->post(
                config('services.monitoring.url') . '/ping',
                ['device' => $request->device]
            );

            return response()->json($response->json());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ping gagal',
            ], 500);
        }
    }

    public function reboot(Request $request)
    {
        $request->validate([
            'device' => 'required|string'
        ]);

        try {
            $response = Http::timeout(5)->post(
                config('services.monitoring.url') . '/reboot',
                ['device' => $request->device]
            );

            return response()->json($response->json());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reboot gagal',
            ], 500);
        }
    }

}
