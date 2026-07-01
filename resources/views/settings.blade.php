<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NetPulse — Settings</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}?v={{ time() }}">
</head>
<body>

@include('partials.navbar')
@include('partials.sidebar')

<div class="toast-container" id="toastContainer"></div>

<div class="drawer-overlay" id="userDrawerOverlay"></div>
<div class="drawer" id="userDrawer">
    <div class="drawer-header">
        <h2 class="drawer-title" id="userDrawerTitle">Add User</h2>
        <button class="drawer-close" id="userDrawerClose"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="editUserId">
        <div>
            <label class="field-label">Full Name *</label>
            <input class="field-input" id="u_name" type="text" placeholder="e.g. John Doe">
        </div>
        <div>
            <label class="field-label">Email Address *</label>
            <input class="field-input" id="u_email" type="email" placeholder="e.g. john@company.com">
        </div>
        <div>
            <label class="field-label">Role *</label>
            <select class="field-select" id="u_role">
                <option value="admin">Admin — Full access</option>
                <option value="operator">Operator — Manage devices & alerts</option>
                <option value="viewer" selected>Viewer — Read only</option>
            </select>
        </div>
        <div>
            <label class="field-label">Password <span id="pwdRequired">*</span></label>
            <div class="field-input-wrap">
                <input class="field-input has-icon" id="u_password" type="password" placeholder="Minimum 8 characters">
                <button class="field-eye-btn" type="button" data-target="u_password">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
            </div>
            <span class="field-hint" id="pwdHint"></span>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-secondary" id="userDrawerCancel">Cancel</button>
        <button class="btn-primary" id="userDrawerSave">
            <span class="material-symbols-outlined">save</span> Save User
        </button>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <span class="material-symbols-outlined" style="font-size:24px;color:#DC2626;font-variation-settings:'FILL' 1;">person_remove</span>
        </div>
        <h3 class="modal-title">Delete User?</h3>
        <p class="modal-desc">This action is permanent. The user will lose all access to NetPulse immediately.</p>
        <div class="modal-actions">
            <button class="btn-cancel" id="deleteCancelBtn">Cancel</button>
            <button class="btn-delete-confirm" id="deleteConfirmBtn">Yes, Delete</button>
        </div>
    </div>
</div>

<main class="main">

    <div class="page-header">
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Manage system configuration, users, and preferences</p>
    </div>

    <div class="settings-tabs">
        <button class="settings-tab active" data-tab="users">
            <span class="material-symbols-outlined">group</span> Users
        </button>
        <button class="settings-tab" data-tab="monitoring">
            <span class="material-symbols-outlined">monitor_heart</span> Monitoring
        </button>
        <button class="settings-tab" data-tab="security">
            <span class="material-symbols-outlined">shield</span> Security
        </button>
        <button class="settings-tab" data-tab="profile">
            <span class="material-symbols-outlined">manage_accounts</span> My Profile
        </button>
        <button class="settings-tab" data-tab="system">
            <span class="material-symbols-outlined">dns</span> System
        </button>
    </div>

    <div class="tab-panel" id="tab-users">
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">group</span></div>
                    <div>
                        <p class="section-title">User Management</p>
                        <p class="section-sub">Manage who has access to NetPulse and their permissions</p>
                    </div>
                </div>
            </div>

            <div class="users-header">
                <div class="users-search-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input class="users-search" id="usersSearch" type="text" placeholder="Search users…">
                </div>
                @can('manage users')
                <button class="btn-primary" id="addUserBtn">
                    <span class="material-symbols-outlined">person_add</span> Add User
                </button>
                @endcan
            </div>

            <div style="border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        @forelse($users as $u)
                        <tr id="user-row-{{ $u->id }}"
                            data-search="{{ strtolower($u->name . ' ' . $u->email) }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="user-avatar {{ $u->currentRoleName() }}">{{ strtoupper(substr($u->name,0,1)) }}</div>
                                    <div>
                                        <p style="margin:0;font-weight:600;font-size:13px;color:#0F172A;">{{ $u->name }}
                                            @if($u->id === auth()->id())
                                            <span style="font-size:10px;font-weight:700;color:#059669;background:#ECFDF5;padding:1px 7px;border-radius:99px;margin-left:4px;">You</span>
                                            @endif
                                        </p>
                                        <p style="margin:0;font-size:11px;color:#94A3B8;">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge role-{{ $u->currentRoleName() }}">{{ $u->currentRoleName() }}</span></td>
                            <td>
                                <span class="status-pill {{ $u->is_active ? 'active' : 'inactive' }}">
                                    <span class="status-pill-dot"></span>
                                    {{ $u->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="color:#94A3B8;font-size:12px;">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}</td>
                            <td style="text-align:right;">
                                @can('manage users')
                                <button class="icon-btn" title="Edit" onclick="openEditUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->currentRoleName() }}')">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="icon-btn" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}" onclick="toggleUser({{ $u->id }})">
                                    <span class="material-symbols-outlined">{{ $u->is_active ? 'person_off' : 'person' }}</span>
                                </button>
                                <button class="icon-btn danger" title="Delete" onclick="confirmDeleteUser({{ $u->id }})" {{ $u->id === auth()->id() ? 'disabled style=opacity:.3;cursor:not-allowed' : '' }}>
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:#94A3B8;">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display:flex;gap:20px;margin-top:16px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="role-badge role-admin">Admin</span>
                    <span style="font-size:11px;color:#94A3B8;">Full system access</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="role-badge role-operator">Operator</span>
                    <span style="font-size:11px;color:#94A3B8;">Manage devices & alerts</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="role-badge role-viewer">Viewer</span>
                    <span style="font-size:11px;color:#94A3B8;">Read-only access</span>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-monitoring">
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">monitor_heart</span></div>
                    <div>
                        <p class="section-title">Monitoring & Polling</p>
                        <p class="section-sub">Control how often devices are checked and alert thresholds</p>
                    </div>
                </div>
            </div>
            <div class="field-grid">
                <div class="field-grid-3">
                    <div>
                        <label class="field-label">Polling Interval (seconds)</label>
                        <input class="field-input" id="m_polling_interval" type="number" min="10" max="3600"
                               value="{{ $monitoring['polling_interval'] ?? 60 }}">
                        <span class="field-hint">How often to ping each device. Min: 10s.</span>
                    </div>
                    <div>
                        <label class="field-label">Latency Threshold (ms)</label>
                        <input class="field-input" id="m_latency_threshold" type="number" min="1"
                               value="{{ $monitoring['latency_threshold'] ?? 100 }}">
                        <span class="field-hint">Mark device as degraded above this value.</span>
                    </div>
                    <div>
                        <label class="field-label">Packet Loss Threshold (%)</label>
                        <input class="field-input" id="m_packet_loss_threshold" type="number" min="0" max="100"
                               value="{{ $monitoring['packet_loss_threshold'] ?? 5 }}">
                        <span class="field-hint">Trigger alert above this packet loss %.</span>
                    </div>
                </div>
                <div>
                    <label class="field-label">Data Retention (days)</label>
                    <input class="field-input" id="m_retention_days" type="number" min="1" max="365"
                           value="{{ $monitoring['retention_days'] ?? 30 }}" style="max-width:200px;">
                    <span class="field-hint">Logs older than this will be purged. Applies to device_status and snmp_metrics.</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
                    <div class="toggle-row">
                        <div class="toggle-row-info">
                            <p class="toggle-row-title">Auto-create Incidents</p>
                            <p class="toggle-row-desc">Automatically create an incident when an alert rule triggers</p>
                        </div>
                        <label class="toggle-wrap">
                            <input type="checkbox" id="m_auto_create_incidents"
                                   {{ ($monitoring['auto_create_incidents'] ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-row-info">
                            <p class="toggle-row-title">Auto-resolve Incidents</p>
                            <p class="toggle-row-desc">Automatically resolve an incident when the device comes back online</p>
                        </div>
                        <label class="toggle-wrap">
                            <input type="checkbox" id="m_auto_resolve_incidents"
                                   {{ ($monitoring['auto_resolve_incidents'] ?? '0') == '1' ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn-primary" id="saveMonitoringBtn">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-security">
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">shield</span></div>
                    <div>
                        <p class="section-title">Security Settings</p>
                        <p class="section-sub">Session management, login protection, and password policy</p>
                    </div>
                </div>
            </div>
            <div class="field-grid">
                <div class="field-grid-3">
                    <div>
                        <label class="field-label">Session Timeout (minutes)</label>
                        <input class="field-input" id="s_session_timeout" type="number" min="5" max="1440"
                               value="{{ $security['session_timeout'] ?? 120 }}">
                        <span class="field-hint">Auto-logout after inactivity.</span>
                    </div>
                    <div>
                        <label class="field-label">Max Login Attempts</label>
                        <input class="field-input" id="s_max_login_attempts" type="number" min="3" max="20"
                               value="{{ $security['max_login_attempts'] ?? 5 }}">
                        <span class="field-hint">Before account is temporarily locked.</span>
                    </div>
                    <div>
                        <label class="field-label">Lockout Duration (minutes)</label>
                        <input class="field-input" id="s_lockout_duration" type="number" min="1" max="60"
                               value="{{ $security['lockout_duration'] ?? 15 }}">
                        <span class="field-hint">How long the account stays locked.</span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
                    <div class="toggle-row">
                        <div class="toggle-row-info">
                            <p class="toggle-row-title">Require Strong Password</p>
                            <p class="toggle-row-desc">Enforce uppercase, number, and symbol in user passwords</p>
                        </div>
                        <label class="toggle-wrap">
                            <input type="checkbox" id="s_require_strong_password"
                                   {{ ($security['require_strong_password'] ?? '0') == '1' ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-row-info">
                            <p class="toggle-row-title">Log All Login Activity</p>
                            <p class="toggle-row-desc">Record every successful and failed login attempt to system logs</p>
                        </div>
                        <label class="toggle-wrap">
                            <input type="checkbox" id="s_log_all_logins"
                                   {{ ($security['log_all_logins'] ?? '1') == '1' ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn-primary" id="saveSecurityBtn">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-profile">
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">manage_accounts</span></div>
                    <div>
                        <p class="section-title">My Profile</p>
                        <p class="section-sub">Update your name, email, and password</p>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="user-avatar {{ auth()->user()->currentRoleName() }}" style="width:42px;height:42px;font-size:16px;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p style="margin:0;font-weight:700;font-size:14px;color:#0F172A;">{{ auth()->user()->name }}</p>
                        <span class="role-badge role-{{ auth()->user()->currentRoleName() }}">{{ auth()->user()->currentRoleName() }}</span>
                    </div>
                </div>
            </div>
            <div class="field-grid">
                <div class="field-grid-2">
                    <div>
                        <label class="field-label">Full Name *</label>
                        <input class="field-input" id="p_name" type="text" value="{{ auth()->user()->name }}">
                    </div>
                    <div>
                        <label class="field-label">Email Address *</label>
                        <input class="field-input" id="p_email" type="email" value="{{ auth()->user()->email }}">
                    </div>
                </div>
                <div style="padding: 16px; background:#F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;">
                    <p style="margin:0 0 14px;font-family:'Inter',sans-serif;font-weight:600;font-size:12px;color:#475569;text-transform:uppercase;letter-spacing:.05em;">Change Password</p>
                    <div class="field-grid-3">
                        <div>
                            <label class="field-label">Current Password</label>
                            <div class="field-input-wrap">
                                <input class="field-input has-icon" id="p_current_password" type="password" placeholder="Enter current">
                                <button class="field-eye-btn" type="button" data-target="p_current_password">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="field-label">New Password</label>
                            <div class="field-input-wrap">
                                <input class="field-input has-icon" id="p_new_password" type="password" placeholder="Min 8 characters">
                                <button class="field-eye-btn" type="button" data-target="p_new_password">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="field-label">Confirm New Password</label>
                            <div class="field-input-wrap">
                                <input class="field-input has-icon" id="p_new_password_confirmation" type="password" placeholder="Repeat new password">
                                <button class="field-eye-btn" type="button" data-target="p_new_password_confirmation">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <span class="field-hint" style="margin-top:8px;display:block;">Leave password fields empty to keep current password.</span>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn-primary" id="saveProfileBtn">
                    <span class="material-symbols-outlined">save</span> Update Profile
                </button>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-system">

        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">dns</span></div>
                    <div>
                        <p class="section-title">System Information</p>
                        <p class="section-sub">Runtime environment and database statistics</p>
                    </div>
                </div>
                <button class="btn-secondary" id="refreshSysInfoBtn">
                    <span class="material-symbols-outlined">refresh</span> Refresh
                </button>
            </div>
            <div class="sysinfo-grid" id="sysinfoGrid">
                <div class="sysinfo-card">
                    <p class="sysinfo-label">Database Size</p>
                    <p class="sysinfo-value" id="si_db_size">—<span class="sysinfo-unit">MB</span></p>
                </div>
                <div class="sysinfo-card">
                    <p class="sysinfo-label">Monitored Devices</p>
                    <p class="sysinfo-value" id="si_devices">—</p>
                </div>
                <div class="sysinfo-card">
                    <p class="sysinfo-label">Status Log Entries</p>
                    <p class="sysinfo-value" id="si_logs">—</p>
                </div>
                <div class="sysinfo-card">
                    <p class="sysinfo-label">SNMP Records</p>
                    <p class="sysinfo-value" id="si_snmp">—</p>
                </div>
                <div class="sysinfo-card">
                    <p class="sysinfo-label">PHP Version</p>
                    <p class="sysinfo-value" style="font-size:16px;" id="si_php">—</p>
                </div>
                <div class="sysinfo-card">
                    <p class="sysinfo-label">Laravel Version</p>
                    <p class="sysinfo-value" style="font-size:16px;" id="si_laravel">—</p>
                </div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="font-size:16px;color:#059669;">schedule</span>
                <span style="font-family:'Inter',sans-serif;font-size:12px;color:#64748B;">Server time: <strong id="si_time">—</strong></span>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">schedule</span></div>
                    <div>
                        <p class="section-title">Alert Engine & Scheduler</p>
                        <p class="section-sub">Cron configuration for automated alert checks</p>
                    </div>
                </div>
            </div>
            <div style="background:#0F172A;border-radius:12px;padding:18px 20px;margin-bottom:16px;">
                <p style="font-family:'Liberation Mono','Courier New',monospace;font-size:12px;color:#86EFAC;margin:0 0 6px;">
                    # Add this to your crontab (cPanel > Cron Jobs)
                </p>
                <p style="font-family:'Liberation Mono','Courier New',monospace;font-size:13px;color:#F1F5F9;margin:0;word-break:break-all;">
                    * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
                </p>
            </div>
            <p style="font-family:'Inter',sans-serif;font-size:12px;color:#94A3B8;margin:0;">
                The scheduler runs <code style="background:#F1F5F9;padding:1px 6px;border-radius:5px;">alerts:check</code> every minute.
                For development, run <code style="background:#F1F5F9;padding:1px 6px;border-radius:5px;">php artisan schedule:work</code> in terminal.
            </p>
            <div style="
                margin-top: 16px;
                padding: 14px 16px;
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
            ">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div id="cronStatusDot" style="
                        width: 10px; height: 10px;
                        border-radius: 50%;
                        background: #94A3B8;
                        flex-shrink: 0;
                    "></div>
                    <div>
                        <p style="margin:0; font-family:'Inter',sans-serif; font-size:13px; font-weight:600; color:#0F172A;" id="cronStatusText">
                            Checking scheduler...
                        </p>
                        <p style="margin:0; font-family:'Inter',sans-serif; font-size:11px; color:#94A3B8;" id="cronStatusSub">
                            Last checked: —
                        </p>
                    </div>
                </div>
                <button class="btn-secondary" id="refreshCronBtn" style="font-size:12px; padding:6px 14px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">refresh</span> Check Status
                </button>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <div class="section-card-title-group">
                    <div class="section-icon"><span class="material-symbols-outlined">cloud_upload</span></div>
                    <div>
                        <p class="section-title">Manual Cloud Backup</p>
                        <p class="section-sub">Push current database records to Supabase immediately</p>
                    </div>
                </div>
            </div>
            <div style="padding: 16px; background:#F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <p style="margin: 0 0 4px; font-family:'Inter',sans-serif; font-weight: 600; font-size: 14px; color: #0F172A;">Trigger Backup to Supabase</p>
                    <p style="margin: 0; font-family:'Inter',sans-serif; font-size: 12px; color: #64748B;">This action runs in the background via the Python API and might take a few minutes.</p>
                </div>
                <button class="btn-primary" id="manualBackupBtn">
                    <span class="material-symbols-outlined">cloud_sync</span> Run Backup
                </button>
            </div>
        </div>

        <div class="danger-zone">
            <h3 class="danger-zone-title">⚠ Danger Zone</h3>
            <p class="danger-zone-sub">These actions are destructive and cannot be undone.</p>
            <div class="danger-actions">
                <button class="btn-danger" id="clearLogsBtn">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Clear Old Logs (retention policy)
                </button>
            </div>
        </div>
    </div>

    @include('partials.footer')
</main>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function showToast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span><span>${msg}</span>`;
    c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 3500);
}

document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        if (tab.dataset.tab === 'system') loadSysInfo();
    });
});

const defaultTab = document.querySelector('.settings-tab.active');
if (defaultTab) {
    defaultTab.click();
}

document.querySelectorAll('.field-eye-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const inp  = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector('.material-symbols-outlined');
        if (inp.type === 'password') { inp.type = 'text'; icon.textContent = 'visibility_off'; }
        else                         { inp.type = 'password'; icon.textContent = 'visibility'; }
    });
});

async function saveSettings(url, body, btn) {
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:14px;animation:spin 1s linear infinite;">progress_activity</span> Saving…';
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const d = await r.json();
        showToast(d.message, d.ok ? 'success' : 'error');
    } catch(e) { showToast('Network error.', 'error'); }
    btn.disabled = false; btn.innerHTML = orig;
}

document.getElementById('saveMonitoringBtn').addEventListener('click', function() {
    saveSettings('/settings/monitoring', {
        polling_interval:        document.getElementById('m_polling_interval').value,
        latency_threshold:       document.getElementById('m_latency_threshold').value,
        packet_loss_threshold:   document.getElementById('m_packet_loss_threshold').value,
        retention_days:          document.getElementById('m_retention_days').value,
        auto_create_incidents:   document.getElementById('m_auto_create_incidents').checked ? 1 : 0,
        auto_resolve_incidents:  document.getElementById('m_auto_resolve_incidents').checked ? 1 : 0,
    }, this);
});

document.getElementById('saveSecurityBtn').addEventListener('click', function() {
    saveSettings('/settings/security', {
        session_timeout:          document.getElementById('s_session_timeout').value,
        max_login_attempts:       document.getElementById('s_max_login_attempts').value,
        lockout_duration:         document.getElementById('s_lockout_duration').value,
        require_strong_password:  document.getElementById('s_require_strong_password').checked ? 1 : 0,
        log_all_logins:           document.getElementById('s_log_all_logins').checked ? 1 : 0,
    }, this);
});

document.getElementById('saveProfileBtn').addEventListener('click', async function() {
    const newPwd  = document.getElementById('p_new_password').value;
    const confPwd = document.getElementById('p_new_password_confirmation').value;
    if (newPwd && newPwd !== confPwd) { showToast('New passwords do not match.', 'error'); return; }
    const body = {
        name:  document.getElementById('p_name').value,
        email: document.getElementById('p_email').value,
    };
    if (newPwd) {
        body.current_password            = document.getElementById('p_current_password').value;
        body.new_password                = newPwd;
        body.new_password_confirmation   = confPwd;
    }
    await saveSettings('/settings/profile', body, this);
});

const userDrawer        = document.getElementById('userDrawer');
const userDrawerOverlay = document.getElementById('userDrawerOverlay');

function openUserDrawer() { userDrawer.classList.add('open'); userDrawerOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeUserDrawer(){ userDrawer.classList.remove('open'); userDrawerOverlay.classList.remove('open'); document.body.style.overflow = ''; }

document.getElementById('addUserBtn').addEventListener('click', () => {
    document.getElementById('userDrawerTitle').textContent = 'Add User';
    document.getElementById('editUserId').value = '';
    document.getElementById('u_name').value = '';
    document.getElementById('u_email').value = '';
    document.getElementById('u_role').value = 'viewer';
    document.getElementById('u_password').value = '';
    document.getElementById('pwdRequired').textContent = '*';
    document.getElementById('pwdHint').textContent = '';
    openUserDrawer();
});

document.getElementById('manualBackupBtn').addEventListener('click', async function() {
    const btn      = this;
    const origHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;animation:spin 1s linear infinite;">progress_activity</span> Processing...';

    try {
        const response = await fetch('/settings/backup/manual', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': CSRF
            }
        });

        const data = await response.json();

        if (data.ok) {
            showToast(data.message || 'Backup dimulai di background!', 'success');
        } else {
            showToast(data.message || 'Gagal memulai backup.', 'error');
        }

    } catch(e) {
        showToast('Network error — tidak dapat menghubungi server.', 'error');
    }

    btn.disabled  = false;
    btn.innerHTML = origHTML;
});

async function checkCronStatus() {
    const dot  = document.getElementById('cronStatusDot');
    const text = document.getElementById('cronStatusText');
    const sub  = document.getElementById('cronStatusSub');

    try {
        const r = await fetch('/settings/system-info');
        const d = await r.json();

        if (d.ok) {
            const hasData = d.log_count > 0;
            dot.style.background  = hasData ? '#10B981' : '#F59E0B';
            dot.style.animation   = hasData ? 'pulse 2s infinite' : 'none';
            text.textContent      = hasData
                ? 'Scheduler Active — Data is being collected'
                : 'No data yet — Run scheduler manually';
            sub.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
        }
    } catch(e) {
        dot.style.background  = '#EF4444';
        text.textContent      = 'Cannot connect to check scheduler status';
        sub.textContent       = `Last checked: ${new Date().toLocaleTimeString()}`;
    }
}

document.getElementById('refreshCronBtn')?.addEventListener('click', checkCronStatus);

const pulseStyle = document.createElement('style');
pulseStyle.textContent = `
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.6; transform: scale(1.3); }
    }
`;
document.head.appendChild(pulseStyle);

function openEditUser(id, name, email, role) {
    document.getElementById('userDrawerTitle').textContent = 'Edit User';
    document.getElementById('editUserId').value = id;
    document.getElementById('u_name').value = name;
    document.getElementById('u_email').value = email;
    document.getElementById('u_role').value = role;
    document.getElementById('u_password').value = '';
    document.getElementById('pwdRequired').textContent = '';
    document.getElementById('pwdHint').textContent = 'Leave blank to keep current password.';
    openUserDrawer();
}

['userDrawerClose','userDrawerCancel'].forEach(id =>
    document.getElementById(id).addEventListener('click', closeUserDrawer));
userDrawerOverlay.addEventListener('click', closeUserDrawer);

document.getElementById('userDrawerSave').addEventListener('click', async function() {
    const id   = document.getElementById('editUserId').value;
    const body = {
        name:     document.getElementById('u_name').value.trim(),
        email:    document.getElementById('u_email').value.trim(),
        role:     document.getElementById('u_role').value,
        password: document.getElementById('u_password').value,
    };
    if (!body.name || !body.email) { showToast('Name and email are required.', 'error'); return; }
    if (!id && !body.password) { showToast('Password is required for new users.', 'error'); return; }

    this.disabled = true;
    try {
        const url    = id ? `/settings/users/${id}` : '/settings/users';
        const method = id ? 'PUT' : 'POST';
        const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) });
        const d = await r.json();
        if (d.ok) { showToast(d.message); closeUserDrawer(); setTimeout(() => location.reload(), 700); }
        else showToast(d.message || 'Save failed.', 'error');
    } catch(e) { showToast('Network error.', 'error'); }
    this.disabled = false;
});

async function toggleUser(id) {
    try {
        const r = await fetch(`/settings/users/${id}/toggle`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } });
        const d = await r.json();
        if (d.ok) { showToast(d.message); setTimeout(() => location.reload(), 700); }
        else showToast(d.message, 'error');
    } catch(e) { showToast('Network error.', 'error'); }
}

let pendingDeleteUserId = null;
const deleteModal = document.getElementById('deleteModal');
function confirmDeleteUser(id) { pendingDeleteUserId = id; deleteModal.classList.add('open'); }
document.getElementById('deleteCancelBtn').addEventListener('click', () => { deleteModal.classList.remove('open'); pendingDeleteUserId = null; });
document.getElementById('deleteConfirmBtn').addEventListener('click', async () => {
    if (!pendingDeleteUserId) return;
    deleteModal.classList.remove('open');
    try {
        const r = await fetch(`/settings/users/${pendingDeleteUserId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
        const d = await r.json();
        if (d.ok) { document.getElementById(`user-row-${pendingDeleteUserId}`)?.remove(); showToast(d.message); }
        else showToast(d.message, 'error');
    } catch(e) { showToast('Network error.', 'error'); }
    pendingDeleteUserId = null;
});

document.getElementById('usersSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#usersTableBody tr[id]').forEach(row => {
        row.style.display = (!term || (row.dataset.search || '').includes(term)) ? '' : 'none';
    });
});

async function loadSysInfo() {
    try {
        const r = await fetch('/settings/system-info');
        const d = await r.json();
        if (!d.ok) return;
        document.getElementById('si_db_size').innerHTML  = d.db_size_mb + '<span class="sysinfo-unit">MB</span>';
        document.getElementById('si_devices').textContent = d.device_count;
        document.getElementById('si_logs').textContent    = Number(d.log_count).toLocaleString();
        document.getElementById('si_snmp').textContent    = Number(d.snmp_count).toLocaleString();
        document.getElementById('si_php').textContent     = d.php_version;
        document.getElementById('si_laravel').textContent = d.laravel_version;
        document.getElementById('si_time').textContent    = d.server_time;
    } catch(e) {  }
}

document.getElementById('refreshSysInfoBtn').addEventListener('click', loadSysInfo);

document.getElementById('clearLogsBtn').addEventListener('click', async function() {
    if (!confirm('This will permanently delete old logs based on your retention policy. Continue?')) return;
    this.disabled = true;
    try {
        const r = await fetch('/settings/clear-logs', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } });
        const d = await r.json();
        showToast(d.message, d.ok ? 'success' : 'error');
    } catch(e) { showToast('Network error.', 'error'); }
    this.disabled = false;
});

const style = document.createElement('style');
style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(style);
</script>
</body>
</html>
