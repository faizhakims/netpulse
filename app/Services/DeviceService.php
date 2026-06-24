<?php

namespace App\Services;

use App\Models\DeviceStatus;
use App\Models\InterfaceTraffic;
use App\Models\Incident;
use App\Models\AlertChannel;
use App\Models\SnmpMetric;
use Illuminate\Support\Facades\Http;

class DeviceService
{
    private function validateMonitoringUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!in_array($parsed['scheme'] ?? '', ['https', 'http'])) {
            throw new \Exception('MONITORING_API_URL harus menggunakan protokol HTTP(S).', 422);
        }

        if (app()->isProduction() && ($parsed['scheme'] ?? '') !== 'https') {
            throw new \Exception('MONITORING_API_URL wajib menggunakan HTTPS di environment production.', 422);
        }

        $host = $parsed['host'] ?? '';
        if (empty($host)) {
            throw new \Exception('MONITORING_API_URL tidak valid: host tidak ditemukan.', 422);
        }

        $allowedHosts = array_filter(array_map(
            'trim',
            explode(',', config('services.monitoring.allowed_hosts', ''))
        ));

        if (!empty($allowedHosts) && !in_array($host, $allowedHosts)) {
            throw new \Exception("Host '{$host}' tidak ada dalam whitelist MONITORING_API_ALLOWED_HOSTS.", 403);
        }

        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP) &&
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            throw new \Exception("Host '{$host}' resolve ke IP private/reserved yang tidak diizinkan.", 403);
        }
    }

    public function getDeviceDetails(string $deviceName): array
    {
        $status = DeviceStatus::where('device', $deviceName)
            ->latest('checked_at')
            ->first();

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

        $statusHistory = DeviceStatus::where('device', $deviceName)
            ->orderByDesc('checked_at')
            ->limit(20)
            ->get();

        $traffic = InterfaceTraffic::whereIn('id', function ($q) use ($deviceName) {
            $q->selectRaw('MAX(id)')
              ->from('interface_traffic')
              ->where('device', $deviceName)
              ->groupBy('interface_name');
        })->orderByDesc('bytes_in')->get();

        $metrics = SnmpMetric::where('device', $deviceName)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                  ->from('snmp_metrics')
                  ->groupBy('device', 'metric_name');
            })
            ->get()
            ->keyBy('metric_name');

        $allStatuses = DeviceStatus::where('device', $deviceName)->get();
        $upCount   = $allStatuses->where('status', 'up')->count();
        $total     = $allStatuses->count();
        $uptimePct = $total > 0 ? round(($upCount / $total) * 100, 2) : 100;

        $isStale       = $status ? $status->isStale() : true;
        $effectiveStatus = $status ? $status->effectiveStatus() : 'unknown';

        $lastReboot = null;
        $sysUpTimeMetric = $metrics->get('sysUpTime');
        if ($sysUpTimeMetric && $sysUpTimeMetric->metric_value) {
            $ticks = (float) $sysUpTimeMetric->metric_value;
            $rebootTime = now()->subSeconds($ticks / 100);
            $lastReboot = $rebootTime->format('d-m-Y \a\t H.i');
        }

        $incidents = Incident::where('device', $deviceName)
            ->orderByDesc('started_at')
            ->get();

        $alertChannelModels = AlertChannel::whereIn('type', ['telegram', 'email'])->get()->keyBy('type');
        $alertChannels = [
            'telegram' => $alertChannelModels->get('telegram')?->is_active ?? false,
            'email'    => $alertChannelModels->get('email')?->is_active ?? false,
        ];

        return compact(
            'status', 'statusHistory', 'traffic', 'metrics', 'deviceName',
            'latencyLabels', 'latencyData', 'latencyAvg', 'latencyPeak', 'latencyMin',
            'uptimePct', 'isStale', 'effectiveStatus', 'lastReboot',
            'incidents', 'alertChannels'
        );
    }

    public function ping(string $deviceName)
    {
        $apiUrl = config('services.monitoring.url');
        if (empty($apiUrl)) {
            throw new \Exception('MONITORING_API_URL belum dikonfigurasi di .env. Hubungi administrator.', 503);
        }

        $this->validateMonitoringUrl($apiUrl);

        $response = Http::timeout(5)->post("{$apiUrl}/ping", ['device' => $deviceName]);
        return $response->json();
    }

    public function reboot(string $deviceName)
    {
        $apiUrl = config('services.monitoring.url');
        if (empty($apiUrl)) {
            throw new \Exception('MONITORING_API_URL belum dikonfigurasi di .env. Hubungi administrator.', 503);
        }

        $this->validateMonitoringUrl($apiUrl);

        $response = Http::timeout(15)->post("{$apiUrl}/reboot", ['device' => $deviceName]);
        return $response->json();
    }

    public function deleteDevice(string $deviceName)
    {
        $apiUrl = config('services.monitoring.url');

        if (empty($apiUrl)) {
            throw new \Exception('MONITORING_API_URL belum dikonfigurasi.', 503);
        }

        $this->validateMonitoringUrl($apiUrl);

        $listResponse = Http::timeout(10)->get("{$apiUrl}/api/devices");
        $devices      = collect($listResponse->json()['data'] ?? []);
        $device       = $devices->firstWhere('name', $deviceName);

        if (!$device) {
            throw new \Exception("Device '{$deviceName}' tidak ditemukan.", 404);
        }

        $deviceId = (int) ($device['id'] ?? 0);
        if ($deviceId <= 0) {
            throw new \Exception("Device ID tidak valid dari monitoring API.", 422);
        }

        $response = Http::timeout(60)->delete("{$apiUrl}/api/devices/{$deviceId}");
        $data     = $response->json();

        if (!$response->successful()) {
            throw new \Exception($data['message'] ?? 'Gagal menghapus device', 500);
        }

        return $data['message'] ?? "Device '{$deviceName}' berhasil dihapus";
    }
}
