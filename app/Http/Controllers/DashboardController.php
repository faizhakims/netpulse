<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index()
    {
        $data = $this->dashboardService->getDashboardData();
        return view('dashboard', $data);
    }

    // ── Ekspor CSV ────────────────────────────────────────────────────────
    public function exportCsv()
    {
        abort_unless(auth()->user()->can('view dashboard'), 403, 'Access denied.');
        $devices = DeviceStatus::latestPerDevice();

        $filename = 'device_inventory_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($devices) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Node Identity', 'IP Address', 'Layer', 'Last Checked', 'Effective Status']);

            foreach ($devices as $device) {
                fputcsv($handle, [
                    $device->device,
                    $device->ip_address,
                    'Network Device',
                    $device->checked_at ? $device->checked_at->diffForHumans() : '-',
                    strtoupper($device->effectiveStatus()),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
