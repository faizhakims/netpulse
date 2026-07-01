<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DeviceResource;
use App\Models\DeviceStatus;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends BaseApiController
{
    public function __construct(private DeviceService $deviceService) {}

    public function index(Request $request)
    {
        if (!auth()->user()->can('view devices')) {
            return $this->error('Forbidden.', 403);
        }

        $devices = DeviceStatus::latestPerDevice();

        if ($search = $request->query('search')) {
            $devices = $devices->filter(fn($d) =>
                str_contains(strtolower($d->device), strtolower($search)) ||
                str_contains($d->ip_address ?? '', $search)
            )->values();
        }

        if ($status = $request->query('status')) {
            $devices = $devices->filter(fn($d) => $d->effectiveStatus() === strtolower($status))->values();
        }

        $sort = $request->query('sort', 'name');
        $devices = match($sort) {
            'latency'  => $devices->sortBy('latency_ms')->values(),
            'status'   => $devices->sortBy(fn($d) => $d->effectiveStatus())->values(),
            'ip'       => $devices->sortBy('ip_address')->values(),
            default    => $devices->sortBy('device')->values(),
        };

        $perPage = max(1, (int) $request->query('per_page', 20));
        $page    = max(1, (int) $request->query('page', 1));
        $total   = $devices->count();
        $items   = $devices->forPage($page, $perPage)->values();

        return $this->success([
            'items'        => DeviceResource::collection($items),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    public function show(string $name)
    {
        if (!auth()->user()->can('view devices')) {
            return $this->error('Forbidden.', 403);
        }

        $status = DeviceStatus::where('device', $name)->latest('checked_at')->first();

        if (!$status) {
            return $this->error("Device '{$name}' not found.", 404);
        }

        $detail = $this->deviceService->getDeviceDetails($name);

        return $this->success([
            'device'          => new DeviceResource($status),
            'uptime_pct'      => $detail['uptimePct'],
            'last_reboot'     => $detail['lastReboot'],
            'latency_avg_ms'  => $detail['latencyAvg'],
            'latency_peak_ms' => $detail['latencyPeak'],
            'latency_min_ms'  => $detail['latencyMin'],
            'alert_channels'  => $detail['alertChannels'],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('manage devices')) {
            return $this->error('Forbidden.', 403);
        }

        $request->validate([
            'device' => 'required|string',
            'action' => 'required|in:ping',
        ]);

        try {
            $result = $this->deviceService->ping($request->device);
            return $this->success($result, 'Ping dispatched.');
        } catch (\Exception $e) {
            $code = $e->getCode();
            return $this->error($e->getMessage(), ($code >= 400 && $code < 600) ? $code : 502);
        }
    }

    public function update(Request $request, string $name)
    {
        if (!auth()->user()->can('manage devices')) {
            return $this->error('Forbidden.', 403);
        }

        $request->validate([
            'action' => 'required|in:reboot',
        ]);

        try {
            $result = $this->deviceService->reboot($name);
            return $this->success($result, 'Reboot dispatched.');
        } catch (\Exception $e) {
            $code = $e->getCode();
            return $this->error($e->getMessage(), ($code >= 400 && $code < 600) ? $code : 502);
        }
    }

    public function destroy(string $name)
    {
        if (!auth()->user()->can('manage devices')) {
            return $this->error('Forbidden.', 403);
        }

        try {
            $message = $this->deviceService->deleteDevice($name);
            return $this->success(null, $message);
        } catch (\Exception $e) {
            $code = $e->getCode();
            return $this->error($e->getMessage(), ($code >= 400 && $code < 600) ? $code : 500);
        }
    }
}
