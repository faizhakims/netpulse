<?php

namespace App\Http\Controllers;

use App\Models\InterfaceTraffic;
use Illuminate\Support\Facades\DB;

class TrafficController extends Controller
{
    public function index()
    {
        // Total bytes keseluruhan
        $totals = DB::table('interface_traffic')
            ->selectRaw('SUM(bytes_in) as total_in, SUM(bytes_out) as total_out,
                         SUM(packets_in) as total_packets_in, SUM(packets_out) as total_packets_out')
            ->first();

        $totalIn    = $totals->total_in    ?? 0;
        $totalOut   = $totals->total_out   ?? 0;
        $totalBytes = $totalIn + $totalOut;

        // Top busiest devices
        $topDevices = DB::table('interface_traffic')
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
