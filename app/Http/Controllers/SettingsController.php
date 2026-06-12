<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemSetting;
use App\Http\Requests\SaveGeneralSettingsRequest;
use App\Http\Requests\SaveMonitoringSettingsRequest;
use App\Http\Requests\SaveSecuritySettingsRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\SettingsService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private UserService $userService
    ) {}

    // ── Main page ─────────────────────────────────────────────────────────────
    public function index()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');

        $users      = User::orderBy('created_at')->get();
        $general    = SystemSetting::group('general');
        $monitoring = SystemSetting::group('monitoring');
        $security   = SystemSetting::group('security');
        $polling    = SystemSetting::group('polling');

        return view('settings', compact('users', 'general', 'monitoring', 'security', 'polling'));
    }

    // ── General settings ──────────────────────────────────────────────────────
    public function saveGeneral(SaveGeneralSettingsRequest $request)
    {
        $this->settingsService->saveGeneral($request->validated());

        return response()->json(['ok' => true, 'message' => 'General settings saved.']);
    }

    // ── Monitoring / polling settings ─────────────────────────────────────────
    public function saveMonitoring(SaveMonitoringSettingsRequest $request)
    {
        $reloadWarning = $this->settingsService->saveMonitoring($request->validated());

        return response()->json([
            'ok'      => true,
            'message' => $reloadWarning ?? 'Monitoring settings saved.',
            'warning' => $reloadWarning !== null,
        ]);
    }

    // ── Security settings ─────────────────────────────────────────────────────
    public function saveSecurity(SaveSecuritySettingsRequest $request)
    {
        $this->settingsService->saveSecurity($request->validated());

        return response()->json(['ok' => true, 'message' => 'Security settings saved.']);
    }

    // ── Profile (change own name/email/password) ──────────────────────────────
    public function saveProfile(UpdateProfileRequest $request)
    {
        try {
            $data = $request->validated();

            $currentPassword = $request->filled('new_password') ? $request->current_password : null;
            $newPassword     = $request->filled('new_password') ? $request->new_password     : null;

            $this->settingsService->updateProfile($data, $currentPassword, $newPassword);

            return response()->json([
                'ok'      => true,
                'message' => 'Profile updated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ── User management ───────────────────────────────────────────────────────
    public function storeUser(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json(['ok' => true, 'message' => 'User created.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
        ]]);
    }

    public function updateUser(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        $user = $this->userService->updateUser($user, $data);

        return response()->json(['ok' => true, 'message' => 'User updated.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
        ]]);
    }

    public function toggleUser($id)
    {
        abort_unless(auth()->user()->can('manage users'), 403, 'Access denied.');

        $user = User::findOrFail($id);

        try {
            $user = $this->userService->toggleUser($user);
            return response()->json(['ok' => true, 'is_active' => $user->is_active,
                'message' => 'User ' . ($user->is_active ? 'activated' : 'deactivated') . '.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteUser($id)
    {
        abort_unless(auth()->user()->can('manage users'), 403, 'Access denied.');

        $user = User::findOrFail($id);

        try {
            $this->userService->deleteUser($user);
            return response()->json(['ok' => true, 'message' => 'User deleted.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── System actions ────────────────────────────────────────────────────────
    public function clearLogs()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');

        $this->settingsService->clearLogs();

        return response()->json(['ok' => true, 'message' => 'Old logs cleared based on retention policy.']);
    }

    public function systemInfo()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');

        $info = $this->settingsService->getSystemInfo();

        return response()->json(array_merge(['ok' => true], $info));
    }

    public function triggerManualBackup()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');

        try {
            $data = $this->settingsService->triggerManualBackup();

            return response()->json([
                'ok'      => true,
                'message' => $data['message'] ?? 'Backup sedang diproses di background.',
                'status'  => $data['status']  ?? 'accepted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], str_contains($e->getMessage(), 'belum dikonfigurasi') ? 503 : 500);
        }
    }
}
