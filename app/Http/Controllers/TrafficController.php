<?php

namespace App\Http\Controllers;

use App\Models\InterfaceTraffic;
use App\Models\DeviceStatus;
use Illuminate\Support\Facades\DB;

class TrafficController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('view traffic'), 403, 'Access denied.');
        // ── 1. BANDWIDTH: ambil snapshot terbaru per device+interface ──────────
        $latestIds = DB::table('interface_traffic')
            ->selectRaw('MAX(id) as id')
            ->groupBy('device', 'interface_name')
            ->pluck('id');

        $latestSnapshots = DB::table('interface_traffic')
            ->whereIn('id', $latestIds)
            ->get();

        $totalIn    = $latestSnapshots->sum('bytes_in');
        $totalOut   = $latestSnapshots->sum('bytes_out');
        $totalBytes = $totalIn + $totalOut;

        // ── 2. BANDWIDTH CHART: data per jam selama 24 jam terakhir ───────────
        $chartData = DB::table('interface_traffic')
            ->selectRaw("
                DATE_FORMAT(collected_at, '%Y-%m-%d %H:00:00') as hour,
                SUM(bytes_in + bytes_out) as total_bytes
            ")
            ->where('collected_at', '>=', now()->subHours(24))
            ->groupByRaw("DATE_FORMAT(collected_at, '%Y-%m-%d %H:00:00')")
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $chartHours  = [];
        $chartValues = [];
        for ($i = 23; $i >= 0; $i--) {
            $hourKey      = now()->subHours($i)->format('Y-m-d H:00:00');
            $chartHours[] = now()->subHours($i)->format('H:00');
            $chartValues[] = $chartData->has($hourKey) ? (int) $chartData[$hourKey]->total_bytes : 0;
        }

        // ── 3. BANDWIDTH LOG: total per hari selama 7 hari terakhir ───────────
        $bandwidthLog = DB::table('interface_traffic')
            ->selectRaw("
                DATE(collected_at) as date,
                SUM(bytes_in)  as total_in,
                SUM(bytes_out) as total_out,
                SUM(bytes_in + bytes_out) as total_bytes
            ")
            ->where('collected_at', '>=', now()->subDays(7))
            ->groupByRaw('DATE(collected_at)')
            ->orderByDesc('date')
            ->get();

        // ── 4. LATENCY: rata-rata device yang UP dari device_status ───────────
        $deviceStatuses = DeviceStatus::latestPerDevice();

        $upDevices = $deviceStatuses->filter(fn($d) => $d->effectiveStatus() === 'up');

        $avgLatency    = null;
        $peakLatency   = null;
        $latencyStatus = 'No Data';

        if ($upDevices->count() > 0) {
            $latencies = $upDevices->pluck('latency_ms')->filter(fn($v) => $v !== null);
            if ($latencies->count() > 0) {
                $avgLatency    = round($latencies->avg(), 1);
                $peakLatency   = round($latencies->max(), 1);
                $latencyStatus = $avgLatency < 50 ? 'Stable' : ($avgLatency < 100 ? 'Moderate' : 'High');
            }
        }

        // ── 5. PACKET LOSS: dari snmp_metrics jika tersedia, else null ─────────
        // Tidak ada data packet loss dari ICMP / SNMP di koleksi ini.
        // Jika koleksi ping_monitor menyimpan is_alive=false → status 'down',
        // kita proxy packet loss sebagai % device down dari total device.
        $packetLoss = null;
        $totalDevices = $deviceStatuses->count();
        if ($totalDevices > 0) {
            // Cek apakah ada metric packet_loss dari SNMP
            $snmpPktLoss = DB::table('snmp_metrics')
                ->where('metric_name', 'packet_loss')
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')
                      ->from('snmp_metrics')
                      ->where('metric_name', 'packet_loss')
                      ->groupBy('device');
                })
                ->avg(DB::raw('CAST(metric_value AS DECIMAL(10,4))'));

            if ($snmpPktLoss !== null) {
                $packetLoss = round((float) $snmpPktLoss, 2);
            }
            // Jika tidak ada SNMP metric, biarkan null → tampil sebagai "-"
        }

        // ── 6. TOP DEVICES + status real ───────────────────────────────────────
        $topDevicesRaw = DB::table('interface_traffic')
            ->whereIn('interface_traffic.id', $latestIds)
            ->selectRaw('
                interface_traffic.device,
                interface_traffic.ip_address,
                SUM(interface_traffic.bytes_in)  as total_in,
                SUM(interface_traffic.bytes_out) as total_out,
                SUM(interface_traffic.bytes_in + interface_traffic.bytes_out) as total_bytes
            ')
            ->groupBy('interface_traffic.device', 'interface_traffic.ip_address')
            ->orderByDesc('total_bytes')
            ->limit(10)
            ->get();

        $statusMap  = $deviceStatuses->keyBy('device');

        $topDevices = $topDevicesRaw->map(function ($row) use ($statusMap) {
            $ds           = $statusMap->get($row->device);
            $row->status   = $ds ? $ds->effectiveStatus() : 'unknown';
            $row->location = null; // Kolom location belum ada di skema DB
            return $row;
        });

        // ── 7. ALL DEVICES untuk slide panel ──────────────────────────────────
        $allDevices = $deviceStatuses->map(function ($ds) use ($latestIds) {
            $bw = DB::table('interface_traffic')
                ->whereIn('id', $latestIds)
                ->where('device', $ds->device)
                ->selectRaw('SUM(bytes_in + bytes_out) as total_bytes')
                ->value('total_bytes');
            $ds->total_bytes = (int) ($bw ?? 0);
            return $ds;
        })->sortByDesc('total_bytes')->values();

        return view('traffic', compact(
            'totalIn', 'totalOut', 'totalBytes',
            'chartHours', 'chartValues',
            'bandwidthLog',
            'avgLatency', 'peakLatency', 'latencyStatus',
            'packetLoss',
            'topDevices',
            'allDevices'
        ));
    }
}