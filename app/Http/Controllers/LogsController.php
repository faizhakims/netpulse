<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Models\InterfaceTraffic;
use App\Models\SnmpMetric;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('view logs'), 403, 'Access denied.');
        // Gabungkan log dari device_status (status changes)
        $statusLogs = DeviceStatus::orderByDesc('checked_at')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                return [
                    'id'          => 'DS-' . $row->id,
                    'date'        => $row->checked_at ? $row->checked_at->format('d M Y') : '-',
                    'date_filter' => $row->checked_at ? $row->checked_at->format('Y-m-d') : '',
                    'time'        => $row->checked_at ? $row->checked_at->format('H:i:s') : '-',
                    'level'  => strtolower($row->status) === 'up' ? 'Info' : 'Critical',
                    'device' => $row->device,
                    'ip'     => $row->ip_address,
                    'event'  => strtolower($row->status) === 'up'
                                    ? 'Device reachable'
                                    : 'Device unreachable / down',
                    'source' => 'ICMP Monitor',
                    'desc'   => 'Latency: ' . ($row->latency_ms !== null ? $row->latency_ms . ' ms' : 'N/A')
                                . ' | Status: ' . strtoupper($row->status),
                    'type'   => 'Network',
                ];
            });

        // Log dari snmp_metrics
        $snmpLogs = SnmpMetric::orderByDesc('collected_at')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'id'          => 'SM-' . $row->id,
                    'date'        => $row->collected_at ? $row->collected_at->format('d M Y') : '-',
                    'date_filter' => $row->collected_at ? $row->collected_at->format('Y-m-d') : '',
                    'time'        => $row->collected_at ? $row->collected_at->format('H:i:s') : '-',
                    'level'       => 'Info',
                    'device'      => $row->device,
                    'ip'          => $row->ip_address,
                    'event'       => 'SNMP Metric collected: ' . $row->metric_name,
                    'source'      => 'SNMP Poller',
                    'desc'        => $row->metric_name . ' = ' . $row->metric_value,
                    'type'        => 'System',
                ];
            });

        // Gabung & urutkan terbaru
        $logs = $statusLogs->concat($snmpLogs)
            ->sortByDesc(fn($l) => $l['date'] . ' ' . $l['time'])
            ->values();

        // Stats
        $totalLogs    = $logs->count();
        $criticalLogs = $logs->where('level', 'Critical')->count();
        $warningLogs  = $logs->where('level', 'Warning')->count();
        $infoLogs     = $logs->where('level', 'Info')->count();

        return view('logs', compact(
            'logs', 'totalLogs', 'criticalLogs', 'warningLogs', 'infoLogs'
        ));
    }
}