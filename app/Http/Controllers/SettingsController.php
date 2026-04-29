<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    // ── Main page ─────────────────────────────────────────────────────────────
    public function index()
    {
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

        return response()->json(['ok' => true, 'message' => 'Monitoring settings saved.']);
    }

    // ── Security settings ─────────────────────────────────────────────────────
    public function saveSecurity(Request $request)
    {
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
        $user = Auth::user();

        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($request->filled('new_password')) {
            $request->validate([
                'current_password' => 'required',
                'new_password'     => ['required', 'confirmed', Password::min(8)],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['ok' => false, 'message' => 'Current password is incorrect.']);
            }

            $data['password'] = Hash::make($request->new_password);
        }

        $user->update($data);

        return response()->json(['ok' => true, 'message' => 'Profile updated successfully.']);
    }

    // ── User management ───────────────────────────────────────────────────────
    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:80',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,operator,viewer',
            'password' => ['required', Password::min(8)],
        ]);

        $data['password']  = Hash::make($data['password']);
        $data['is_active'] = true;
        $user = User::create($data);

        return response()->json(['ok' => true, 'message' => 'User created.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
        ]]);
    }

    public function updateUser(Request $request, $id)
    {
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

        return response()->json(['ok' => true, 'message' => 'User updated.', 'user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
        ]]);
    }

    public function toggleUser($id)
    {
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
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return response()->json(['ok' => false, 'message' => 'Cannot delete your own account.']);
        }
        $user->delete();
        return response()->json(['ok' => true, 'message' => 'User deleted.']);
    }

    // ── System actions ────────────────────────────────────────────────────────
    public function clearLogs()
    {
        \Illuminate\Support\Facades\DB::table('snmp_metrics')
            ->where('collected_at', '<', now()->subDays(
                (int) SystemSetting::get('retention_days', 30)
            ))->delete();

        return response()->json(['ok' => true, 'message' => 'Old logs cleared based on retention policy.']);
    }

    public function systemInfo()
    {
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
}
