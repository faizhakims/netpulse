<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Models\InterfaceTraffic;
use App\Models\Incident;
use App\Models\AlertChannel;
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

        // Riwayat status (20 terakhir)
        $statusHistory = DeviceStatus::where('device', $deviceName)
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get();

        // Traffic interfaces — hanya record terbaru per interface (Bug #10 fix)
        $traffic = InterfaceTraffic::whereIn('id', function ($q) use ($deviceName) {
            $q->selectRaw('MAX(id)')
              ->from('interface_traffic')
              ->where('device', $deviceName)
              ->groupBy('interface_name');
        })->orderByDesc('bytes_in')->get();

        // SNMP metrics
        $metrics = SnmpMetric::where('device', $deviceName)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device', 'metric_name');
            })
            ->get()
            ->keyBy('metric_name');

        // Uptime % dari seluruh history
        $allStatuses = DeviceStatus::where('device', $deviceName)->get();
        $upCount   = $allStatuses->where('status', 'up')->count();
        $total     = $allStatuses->count();
        $uptimePct = $total > 0 ? round(($upCount / $total) * 100, 2) : 100;

        // Staleness info untuk ditampilkan di view
        $isStale       = $status ? $status->isStale() : true;
        $effectiveStatus = $status ? $status->effectiveStatus() : 'unknown';

        $lastReboot = null;
        $sysUpTimeMetric = $metrics->get('sysUpTime');
        if ($sysUpTimeMetric && $sysUpTimeMetric->metric_value) {
            $ticks = (float) $sysUpTimeMetric->metric_value; // dalam hundredths of second
            $rebootTime = now()->subSeconds($ticks / 100);
            $lastReboot = $rebootTime->format('d-m-Y \a\t H.i');
        }

        // REVISI 2: Ambil semua incident untuk device ini (5 terakhir di card, semua di slider)
        $incidents = Incident::where('device', $deviceName)
            ->orderByDesc('started_at')
            ->get();

        // REVISI 3: Status aktif alert channel (Telegram & Email) dari DB
        $alertChannelModels = AlertChannel::whereIn('type', ['telegram', 'email'])->get()->keyBy('type');
        $alertChannels = [
            'telegram' => $alertChannelModels->get('telegram')?->is_active ?? false,
            'email'    => $alertChannelModels->get('email')?->is_active ?? false,
        ];

        return view('details', compact(
            'status', 'statusHistory', 'traffic', 'metrics', 'deviceName',
            'latencyLabels', 'latencyData', 'latencyAvg', 'latencyPeak', 'latencyMin',
            'uptimePct', 'isStale', 'effectiveStatus', 'lastReboot',
            'incidents', 'alertChannels'
        ));

    }

    public function ping(Request $request)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');
        $request->validate(['device' => 'required|string']);

        $apiUrl = config('services.monitoring.url');
        if (empty($apiUrl)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'MONITORING_API_URL belum dikonfigurasi di .env. Hubungi administrator.',
            ], 503);
        }

        try {
            $response = Http::timeout(5)->post("{$apiUrl}/ping", ['device' => $request->device]);
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ping gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reboot(Request $request)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');
        $request->validate(['device' => 'required|string']);

        $apiUrl = config('services.monitoring.url');
        if (empty($apiUrl)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'MONITORING_API_URL belum dikonfigurasi di .env. Hubungi administrator.',
            ], 503);
        }

        try {
            $response = Http::timeout(15)->post("{$apiUrl}/reboot", ['device' => $request->device]);
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Reboot gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteDevice(string $deviceName)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');
        $apiUrl = config('services.monitoring.url');

        if (empty($apiUrl)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'MONITORING_API_URL belum dikonfigurasi.',
            ], 503);
        }

        try {
            // Cari ID device dari Python API
            $listResponse = Http::timeout(10)->get("{$apiUrl}/api/devices");
            $devices      = collect($listResponse->json()['data'] ?? []);
            $device       = $devices->firstWhere('name', $deviceName);

            if (!$device) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Device '{$deviceName}' tidak ditemukan.",
                ], 404);
            }

            // Hapus via Python API — akan archive ke Supabase otomatis
            $response = Http::timeout(60)->delete("{$apiUrl}/api/devices/{$device['id']}");
            $data     = $response->json();

            return response()->json([
                'status'  => $response->successful() ? 'ok'    : 'error',
                'message' => $data['message'] ?? ($response->successful()
                    ? "Device '{$deviceName}' berhasil dihapus"
                    : 'Gagal menghapus device'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke monitoring API: ' . $e->getMessage(),
            ], 500);
        }
    }
}
