<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

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
    public function saveGeneral(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        
        $data = $request->validate([
            'site_name'    => 'required|string|max:80',
            'site_timezone'=> 'required|string|max:60',
            'date_format'  => 'required|string|max:40',
            'site_language'=> 'required|string|max:10',
        ]);

        $this->settingsService->saveGeneral($data);

        return response()->json(['ok' => true, 'message' => 'General settings saved.']);
    }

    // ── Monitoring / polling settings ─────────────────────────────────────────
    public function saveMonitoring(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        
        $data = $request->validate([
            'polling_interval'       => 'required|integer|min:10|max:3600',
            'latency_threshold'      => 'required|numeric|min:1',
            'packet_loss_threshold'  => 'required|numeric|min:0|max:100',
            'retention_days'         => 'required|integer|min:1|max:365',
            'auto_resolve_incidents' => 'boolean',
            'auto_create_incidents'  => 'boolean',
        ]);

        $reloadWarning = $this->settingsService->saveMonitoring($data);

        return response()->json([
            'ok'      => true,
            'message' => $reloadWarning ?? 'Monitoring settings saved.',
            'warning' => $reloadWarning !== null,
        ]);
    }

    // ── Security settings ─────────────────────────────────────────────────────
    public function saveSecurity(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        
        $data = $request->validate([
            'session_timeout'         => 'required|integer|min:5|max:1440',
            'max_login_attempts'      => 'required|integer|min:3|max:20',
            'lockout_duration'        => 'required|integer|min:1|max:60',
            'require_strong_password' => 'boolean',
            'log_all_logins'          => 'boolean',
        ]);

        $this->settingsService->saveSecurity($data);

        return response()->json(['ok' => true, 'message' => 'Security settings saved.']);
    }

    // ── Profile (change own name/email/password) ──────────────────────────────
    public function saveProfile(Request $request)
    {
        try {
            $data = $request->validate([
                'name'  => 'required|string|max:80',
                'email' => 'required|email|unique:users,email,' . Auth::id(),
            ]);

            $currentPassword = null;
            $newPassword = null;

            if ($request->filled('new_password')) {
                $request->validate([
                    'current_password' => 'required',
                    'new_password'     => ['required', 'confirmed', Password::min(8)],
                ]);
                $currentPassword = $request->current_password;
                $newPassword = $request->new_password;
            }

            $this->settingsService->updateProfile($data, $currentPassword, $newPassword);

            return response()->json([
                'ok'      => true,
                'message' => 'Profile updated successfully.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ── User management ───────────────────────────────────────────────────────
    public function storeUser(Request $request)
    {
        abort_unless(auth()->user()->can('manage users'), 403, 'Access denied.');
        
        $data = $request->validate([
            'name'     => 'required|string|max:80',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,operator,viewer',
            'password' => ['required', Password::min(8)],
        ]);

        $user = $this->userService->createUser($data);

        return response()->json(['ok' => true, 'message' => 'User created.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
        ]]);
    }

    public function updateUser(Request $request, $id)
    {
        abort_unless(auth()->user()->can('manage users'), 403, 'Access denied.');
        
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'email' => 'required|email|unique:users,email,' . $id,
            'role'  => 'required|in:admin,operator,viewer',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => Password::min(8)]);
            $data['password'] = $request->password;
        }

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
