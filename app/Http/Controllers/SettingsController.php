<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    // ── Main page ─────────────────────────────────────────────────────────────
    public function index()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');

        $users    = User::orderBy('created_at')->get();
        $general  = SystemSetting::group('general');
        $monitoring = SystemSetting::group('monitoring');
        $security = SystemSetting::group('security');
        $polling  = SystemSetting::group('polling');

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

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'general');
        }

        return response()->json(['ok' => true, 'message' => 'General settings saved.']);
    }

    // ── Monitoring / polling settings ─────────────────────────────────────────
    public function saveMonitoring(Request $request)
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        $data = $request->validate([
            'polling_interval'   => 'required|integer|min:10|max:3600',
            'latency_threshold'  => 'required|numeric|min:1',
            'packet_loss_threshold' => 'required|numeric|min:0|max:100',
            'retention_days'     => 'required|integer|min:1|max:365',
            'auto_resolve_incidents' => 'boolean',
            'auto_create_incidents'  => 'boolean',
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'monitoring');
        }

        // Coba notify Python service supaya reload interval dari DB.
        // Jika endpoint /config/reload belum ada atau service mati, tetap return ok
        // tapi sertakan peringatan agar user tahu perlu restart manual.
        $reloadWarning = null;
        $apiUrl = config('services.monitoring.url');
        if (!empty($apiUrl)) {
            try {
                $resp = \Illuminate\Support\Facades\Http::timeout(3)->post("{$apiUrl}/config/reload", [
                    'ping_interval' => (int) $data['polling_interval'],
                    'snmp_interval' => (int) $data['polling_interval'],
                    'bw_interval'   => (int) $data['polling_interval'],
                ]);
                // Jika endpoint belum ada (404) atau gagal, tampilkan warning
                if (!$resp->successful()) {
                    $reloadWarning = 'Settings saved. Perubahan polling interval memerlukan restart Python monitoring service agar berlaku.';
                }
            } catch (\Exception $e) {
                $reloadWarning = 'Settings saved. Python monitoring service tidak dapat dihubungi — restart service secara manual agar perubahan polling interval berlaku.';
            }
        } else {
            $reloadWarning = 'Settings saved. MONITORING_API_URL belum dikonfigurasi, perubahan polling interval tidak dapat dikirim ke monitoring service.';
        }

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
            'session_timeout'    => 'required|integer|min:5|max:1440',
            'max_login_attempts' => 'required|integer|min:3|max:20',
            'lockout_duration'   => 'required|integer|min:1|max:60',
            'require_strong_password' => 'boolean',
            'log_all_logins'     => 'boolean',
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, 'security');
        }

        return response()->json(['ok' => true, 'message' => 'Security settings saved.']);
    }

    // ── Profile (change own name/email/password) ──────────────────────────────
    public function saveProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validasi dasar
        // Note: 'role' is intentionally excluded here to prevent privilege escalation.
        try {
            $data = $request->validate([
                'name'  => 'required|string|max:80',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        // Ganti password jika diisi
        if ($request->filled('new_password')) {
            try {
                $request->validate([
                    'current_password' => 'required',
                    'new_password'     => ['required', 'confirmed', Password::min(8)],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'ok'      => false,
                    'message' => collect($e->errors())->flatten()->first(),
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            $data['password'] = Hash::make($request->new_password);
        }

        $user->update($data);

        return response()->json([
            'ok'      => true,
            'message' => 'Profile updated successfully.',
        ]);
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

        $data['password']  = Hash::make($data['password']);
        $data['is_active'] = true;
        $user = User::create($data);
        $user->syncRoles([$data['role']]); // Sync Spatie role

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
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]); // Sync Spatie role

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
        if ($user->id === Auth::id()) {
            return response()->json(['ok' => false, 'message' => 'Cannot deactivate your own account.']);
        }
        $user->is_active = !$user->is_active;
        $user->save();
        return response()->json(['ok' => true, 'is_active' => $user->is_active,
            'message' => 'User ' . ($user->is_active ? 'activated' : 'deactivated') . '.']);
    }

    public function deleteUser($id)
    {
        abort_unless(auth()->user()->can('manage users'), 403, 'Access denied.');
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return response()->json(['ok' => false, 'message' => 'Cannot delete your own account.']);
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return response()->json(['ok' => false, 'message' => 'Cannot delete the last admin account.']);
        }

        $user->delete();
        return response()->json(['ok' => true, 'message' => 'User deleted.']);
    }

    // ── System actions ────────────────────────────────────────────────────────
    public function clearLogs()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        \Illuminate\Support\Facades\DB::table('snmp_metrics')
            ->where('collected_at', '<', now()->subDays(
                (int) SystemSetting::get('retention_days', 30)
            ))->delete();

        return response()->json(['ok' => true, 'message' => 'Old logs cleared based on retention policy.']);
    }

    public function systemInfo()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        $dbSize = \Illuminate\Support\Facades\DB::select("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ")[0]->size_mb ?? 0;

        $deviceCount  = \Illuminate\Support\Facades\DB::table('device_status')->distinct('device')->count('device');
        $logCount     = \Illuminate\Support\Facades\DB::table('device_status')->count();
        $snmpCount    = \Illuminate\Support\Facades\DB::table('snmp_metrics')->count();
        $trafficCount = \Illuminate\Support\Facades\DB::table('interface_traffic')->count();

        return response()->json([
            'ok'           => true,
            'db_size_mb'   => $dbSize,
            'device_count' => $deviceCount,
            'log_count'    => $logCount,
            'snmp_count'   => $snmpCount,
            'traffic_count'=> $trafficCount,
            'php_version'  => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time'  => now()->format('d M Y H:i:s') . ' WIB',
        ]);
    }

    public function triggerManualBackup()
    {
        abort_unless(auth()->user()->can('manage settings'), 403, 'Access denied.');
        $apiUrl = config('services.monitoring.url');

        if (empty($apiUrl)) {
            return response()->json([
                'ok'      => false,
                'message' => 'MONITORING_API_URL belum dikonfigurasi di .env.',
            ], 503);
        }

        try {
            $response = Http::timeout(10)->post("{$apiUrl}/api/backup/manual");
            $data     = $response->json();

            return response()->json([
                'ok'      => true,
                'message' => $data['message'] ?? 'Backup sedang diproses di background.',
                'status'  => $data['status']  ?? 'accepted',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal menghubungi monitoring API: ' . $e->getMessage(),
            ], 500);
        }
    }
}
