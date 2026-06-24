<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    public function saveGeneral(array $data)
    {
        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'general');
        }
    }

    public function saveMonitoring(array $data)
    {
        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'monitoring');
        }

        $reloadWarning = null;
        $apiUrl = config('services.monitoring.url');
        
        if (!empty($apiUrl)) {
            try {
                $resp = Http::timeout(3)->post("{$apiUrl}/config/reload", [
                    'ping_interval' => (int) $data['polling_interval'],
                    'snmp_interval' => (int) $data['polling_interval'],
                    'bw_interval'   => (int) $data['polling_interval'],
                ]);
                if (!$resp->successful()) {
                    $reloadWarning = 'Settings saved. Perubahan polling interval memerlukan restart Python monitoring service agar berlaku.';
                }
            } catch (\Exception $e) {
                $reloadWarning = 'Settings saved. Python monitoring service tidak dapat dihubungi — restart service secara manual agar perubahan polling interval berlaku.';
            }
        } else {
            $reloadWarning = 'Settings saved. MONITORING_API_URL belum dikonfigurasi, perubahan polling interval tidak dapat dikirim ke monitoring service.';
        }

        return $reloadWarning;
    }

    public function saveSecurity(array $data)
    {
        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'security');
        }
    }

    public function updateProfile(array $data, ?string $currentPassword = null, ?string $newPassword = null)
    {
        $user = Auth::user();

        if ($newPassword) {
            if (!Hash::check($currentPassword, $user->password)) {
                throw new \Exception('Current password is incorrect.');
            }
            $data['password'] = Hash::make($newPassword);
        }

        $user->update($data);
    }

    public function clearLogs()
    {
        DB::table('snmp_metrics')
            ->where('collected_at', '<', now()->subDays(
                (int) SystemSetting::get('retention_days', 30)
            ))->delete();
    }

    public function getSystemInfo()
    {
        $dbSize = DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ")[0]->size_mb ?? 0;

        $deviceCount  = DB::table('device_status')->distinct('device_id')->count('device_id');
        $logCount     = DB::table('device_status')->count();
        $snmpCount    = DB::table('snmp_metrics')->count();
        $trafficCount = DB::table('interface_traffic')->count();

        $isAdmin = Auth::user()?->hasRole('admin') ?? false;

        return [
            'db_size_mb'      => $dbSize,
            'device_count'    => $deviceCount,
            'log_count'       => $logCount,
            'snmp_count'      => $snmpCount,
            'traffic_count'   => $trafficCount,
            'php_version'     => $isAdmin ? PHP_VERSION : 'N/A',
            'laravel_version' => $isAdmin ? app()->version() : 'N/A',
            'server_time'     => now()->format('d M Y H:i:s') . ' WIB',
        ];
    }


    public function triggerManualBackup()
    {
        $apiUrl = config('services.monitoring.url');

        if (empty($apiUrl)) {
            throw new \Exception('MONITORING_API_URL belum dikonfigurasi di .env.');
        }

        $response = Http::timeout(10)->post("{$apiUrl}/api/backup/manual");
        
        if (!$response->successful()) {
            throw new \Exception('Gagal menghubungi monitoring API.');
        }

        return $response->json();
    }
}
