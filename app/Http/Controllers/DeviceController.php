<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        private DeviceService $deviceService
    ) {}

    public function index()
    {
        $devices = DeviceStatus::latestPerDevice();
        return view('device', compact('devices'));
    }

    public function show(string $deviceName)
    {
        $data = $this->deviceService->getDeviceDetails($deviceName);
        return view('details', $data);
    }

    public function ping(Request $request)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');
        $request->validate(['device' => 'required|string']);

        try {
            $result = $this->deviceService->ping($request->device);
            return response()->json($result);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code < 100 || $code > 599) $code = 500;
            return response()->json([
                'status'  => 'error',
                'message' => 'Ping gagal: ' . $e->getMessage(),
            ], $code);
        }
    }

    public function reboot(Request $request)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');
        $request->validate(['device' => 'required|string']);

        try {
            $result = $this->deviceService->reboot($request->device);
            return response()->json($result);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code < 100 || $code > 599) $code = 500;
            return response()->json([
                'status'  => 'error',
                'message' => 'Reboot gagal: ' . $e->getMessage(),
            ], $code);
        }
    }

    public function deleteDevice(string $deviceName)
    {
        abort_unless(auth()->user()->can('manage devices'), 403, 'Access denied.');

        try {
            $message = $this->deviceService->deleteDevice($deviceName);
            return response()->json([
                'status'  => 'ok',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code == 0) $code = 500;
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $code);
        }
    }
}
