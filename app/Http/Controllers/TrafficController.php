<?php

namespace App\Http\Controllers;

use App\Models\InterfaceTraffic;
use Illuminate\Support\Facades\DB;

class TrafficController extends Controller
{
    public function index()
    {
        // Ambil record terbaru per device+interface saja (bukan SUM semua history
        // karena bytes_in/out adalah counter kumulatif SNMP, bukan delta per interval)
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

        // Top busiest devices — berdasarkan snapshot terbaru per device
        $topDevices = DB::table('interface_traffic')
            ->whereIn('id', $latestIds)
            ->selectRaw('device, ip_address,
                         SUM(bytes_in) as total_in,
                         SUM(bytes_out) as total_out,
                         SUM(bytes_in + bytes_out) as total_bytes')
            ->groupBy('device', 'ip_address')
            ->orderByDesc('total_bytes')
            ->limit(10)
            ->get();

        // Detail per interface (terbaru)
        $interfaces = InterfaceTraffic::latestPerInterface();

        return view('traffic', compact(
            'totalIn', 'totalOut', 'totalBytes', 'topDevices', 'interfaces'
        ));
    }
}
