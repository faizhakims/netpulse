<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NetPulse — Alert Configuration</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/alert.css') }}">
</head>
<body>

@include('partials.navbar')
@include('partials.sidebar')

{{-- ══ TOAST CONTAINER ══ --}}
<div class="toast-container" id="toastContainer"></div>

{{-- ══ DRAWER OVERLAY + DRAWER (Add/Edit Rule) ══ --}}
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="drawer" id="ruleDrawer">
    <div class="drawer-header">
        <h2 class="drawer-title" id="drawerTitle">Add Alert Rule</h2>
        <button class="drawer-close" id="drawerCloseBtn">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="drawer-body">
        <input type="hidden" id="editRuleId">

        {{-- Rule Name --}}
        <div>
            <label class="field-label">Rule Name *</label>
            <input class="field-input" id="d_title" type="text" placeholder="e.g. High Latency Alert">
        </div>

        {{-- Target Device --}}
        <div>
            <label class="field-label">Target Device / Group</label>
            <input class="field-input" id="d_target_device" type="text" placeholder="e.g. Core-Router-01 or leave blank for all">
        </div>

        {{-- Metric + Condition --}}
        <div class="drawer-field-row">
            <div>
                <label class="field-label">Metric Type *</label>
                <select class="field-select" id="d_metric_type">
                    <option value="latency">Latency</option>
                    <option value="status">Status</option>
                    <option value="cpu">CPU</option>
                    <option value="memory">Memory</option>
                    <option value="bandwidth">Bandwidth</option>
                    <option value="packet_loss">Packet Loss</option>
                </select>
            </div>
            <div>
                <label class="field-label">Condition *</label>
                <select class="field-select" id="d_condition">
                    <option value="gt">&gt; Greater than</option>
                    <option value="lt">&lt; Less than</option>
                    <option value="eq">= Equals</option>
                    <option value="is_down">is DOWN</option>
                    <option value="is_up">is UP</option>
                </select>
            </div>
        </div>

        {{-- Threshold + Duration --}}
        <div class="drawer-field-row">
            <div>
                <label class="field-label">Threshold Value</label>
                <input class="field-input" id="d_threshold_value" type="number" placeholder="e.g. 100">
            </div>
            <div>
                <label class="field-label">Duration *</label>
                <select class="field-select" id="d_duration">
                    <option value="1m">1 minute</option>
                    <option value="5m" selected>5 minutes</option>
                    <option value="10m">10 minutes</option>
                    <option value="15m">15 minutes</option>
                    <option value="30m">30 minutes</option>
                </select>
            </div>
        </div>

        {{-- Severity --}}
        <div>
            <label class="field-label">Severity *</label>
            <div class="severity-radio-group">
                <label class="severity-radio-label sev-info" data-val="info">
                    <input type="radio" name="d_severity" value="info"> Info
                </label>
                <label class="severity-radio-label sev-warning" data-val="warning">
                    <input type="radio" name="d_severity" value="warning"> Warning
                </label>
                <label class="severity-radio-label sev-critical" data-val="critical">
                    <input type="radio" name="d_severity" value="critical"> Critical
                </label>
            </div>
        </div>

        {{-- Notification Channel --}}
        <div>
            <label class="field-label">Notification Channel *</label>
            <div class="channel-check-group">
                <label class="channel-check-label" id="chk_telegram">
                    <input type="checkbox" value="telegram">
                    <span class="material-symbols-outlined" style="color:#2AABEE;">send</span>
                    Telegram
                </label>
                <label class="channel-check-label" id="chk_email">
                    <input type="checkbox" value="email">
                    <span class="material-symbols-outlined" style="color:#F59E0B;">mail</span>
                    Email
                </label>
            </div>
        </div>

        {{-- Enable toggle --}}
        <div style="display:flex;align-items:center;gap:14px;">
            <label class="toggle-wrap">
                <input type="checkbox" id="d_is_active" checked>
                <span class="toggle-track"></span>
            </label>
            <span style="font-family:'Inter',sans-serif;font-size:13px;color:#475569;font-weight:500;">Enable rule immediately</span>
        </div>
    </div>
    <div class="drawer-footer">
        <button class="btn-cancel" id="drawerCancelBtn">Cancel</button>
        <button class="btn-primary" id="drawerSaveBtn">
            <span class="material-symbols-outlined" style="font-size:14px;">save</span>
            Save Rule
        </button>
    </div>
</div>

{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <span class="material-symbols-outlined" style="font-size:24px;color:#DC2626;font-variation-settings:'FILL' 1;">delete</span>
        </div>
        <h3 class="modal-title">Delete Rule?</h3>
        <p class="modal-desc">This action cannot be undone. The rule and its history will be permanently removed.</p>
        <div class="modal-actions">
            <button class="btn-cancel" id="deleteCancelBtn">Cancel</button>
            <button class="btn-danger" id="deleteConfirmBtn">Yes, Delete</button>
        </div>
    </div>
</div>

{{-- ══ HISTORY SLIDE PANEL ══ --}}
<div class="history-panel-overlay" id="historyPanelOverlay"></div>
<div class="history-panel" id="historyPanel">
    <div class="history-panel-header">
        <h2 class="history-panel-title">Full Alert History</h2>
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="panel-export-group">
                <button class="btn-export" id="panelExportCsvBtn">Export .CSV</button>
                <button class="btn-export" id="panelExportPdfBtn">Export .PDF</button>
            </div>
            <button class="drawer-close" id="historyPanelCloseBtn">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>
    <div class="history-panel-filters">
        <div class="search-wrap" style="flex:1;min-width:160px;">
            <span class="material-symbols-outlined">search</span>
            <input class="search-input" id="panelSearch" type="text" placeholder="Search history…">
        </div>
        <select class="filter-select" id="panelStatusFilter">
            <option value="">Status: All</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
        </select>
        <select class="filter-select" id="panelChannelFilter">
            <option value="">Channel: All</option>
            <option value="telegram">Telegram</option>
            <option value="email">Email</option>
        </select>
        <input class="date-input" type="date" id="panelDateFilter">
    </div>
    <div class="history-panel-body">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Channel</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody id="panelHistoryBody">
                @forelse($allHistory as $log)
                <tr
                    data-status="{{ $log->status }}"
                    data-channel="{{ $log->channel }}"
                    data-date="{{ $log->sent_at->format('Y-m-d') }}"
                    data-search="{{ strtolower($log->recipient . ' ' . $log->message) }}">
                    <td class="mono">{{ $log->sent_at->format('d M Y, H:i') }}</td>
                    <td>
                        <div class="channel-cell">
                            @if($log->channel === 'telegram')
                                <span class="channel-icon telegram material-symbols-outlined">send</span> Telegram
                            @else
                                <span class="channel-icon email material-symbols-outlined">mail</span> Email
                            @endif
                        </div>
                    </td>
                    <td><span class="mono">{{ $log->recipient }}</span></td>
                    <td>
                        <span class="status-badge {{ $log->status }}">
                            <span class="material-symbols-outlined">{{ $log->status === 'sent' ? 'check_circle' : 'cancel' }}</span>
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->message }}">{{ $log->message }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#94A3B8;">No alert history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══ MAIN ══ --}}
<main class="main">

    {{-- Page Header --}}
    <div class="page-header">
        <h1 class="page-title">Alert Configuration</h1>
        <p class="page-subtitle">Manage notification channels and threshold rules</p>
    </div>

    {{-- Stats Row --}}
    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Active Rules</span>
            <span class="stat-value" id="statActiveRules">{{ $activeRules }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Alert Sent (24H)</span>
            <span class="stat-value">{{ $sentLast24h }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Success Rate</span>
            <span class="stat-value green">{{ $successRate }}<small style="font-size:18px;">%</small></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Failed Alerts</span>
            <span class="stat-value red">{{ $failedAlerts }}</span>
        </div>
    </div>

    {{-- ══ Channel Settings ══ --}}
    <div class="settings-row">

        {{-- Telegram --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-title-group">
                    <div class="settings-card-icon">
                        <span class="material-symbols-outlined" style="font-size:18px;color:#2AABEE;">send</span>
                    </div>
                    <div>
                        <p class="settings-card-title">Telegram Settings</p>
                        <p class="settings-card-sub">Configure bot delivery</p>
                    </div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" id="tg_active" {{ $telegram?->is_active ? 'checked' : '' }}>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <div class="field-grid">
                <div>
                    <label class="field-label">Bot Token</label>
                    <div class="field-input-wrap">
                        <input class="field-input has-icon" id="tg_token" type="password"
                               value="{{ $telegram?->config['token'] ?? '' }}" placeholder="Enter bot token">
                        <button class="field-eye-btn" type="button" data-target="tg_token">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="field-label">Chat ID</label>
                    <input class="field-input" id="tg_chat_id" type="text"
                           value="{{ $telegram?->config['chat_id'] ?? '' }}" placeholder="Enter chat ID">
                </div>
            </div>
            <div class="card-actions">
                <button class="btn-reset" data-type="telegram">Reset</button>
                <div style="display:flex;gap:8px;">
                    <button class="btn-primary" style="background:#475569;" data-test="telegram">
                        <span class="material-symbols-outlined" style="font-size:14px;">wifi_tethering</span>
                        Test
                    </button>
                    <button class="btn-primary" data-save="telegram">
                        <span class="material-symbols-outlined" style="font-size:14px;">save</span>
                        Save
                    </button>
                </div>
            </div>
        </div>

        {{-- Email SMTP --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="settings-card-title-group">
                    <div class="settings-card-icon">
                        <span class="material-symbols-outlined" style="font-size:18px;color:#F59E0B;">mail</span>
                    </div>
                    <div>
                        <p class="settings-card-title">Email SMTP Settings</p>
                        <p class="settings-card-sub">Configure email delivery</p>
                    </div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" id="email_active" {{ $emailCfg?->is_active ? 'checked' : '' }}>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <div class="field-grid">
                <div class="field-grid-2">
                    <div>
                        <label class="field-label">SMTP Server</label>
                        <input class="field-input" id="email_host" type="text"
                               value="{{ $emailCfg?->config['host'] ?? '' }}" placeholder="smtp.example.com">
                    </div>
                    <div>
                        <label class="field-label">Port</label>
                        <input class="field-input" id="email_port" type="text"
                               value="{{ $emailCfg?->config['port'] ?? '' }}" placeholder="587">
                    </div>
                </div>
                <div class="field-grid-2">
                    <div>
                        <label class="field-label">Username</label>
                        <input class="field-input" id="email_username" type="text"
                               value="{{ $emailCfg?->config['username'] ?? '' }}" placeholder="resend">
                    </div>
                    <div>
                        <label class="field-label">Password / API Key</label>
                        <div class="field-input-wrap">
                            <input class="field-input has-icon" id="email_password" type="password"
                                   value="{{ $emailCfg?->config['password'] ?? '' }}" placeholder="re_xxxxxxxx">
                            <button class="field-eye-btn" type="button" data-target="email_password">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="field-grid-2">
                    <div>
                        <label class="field-label">From Address <span style="color:#94A3B8;font-weight:400;">(domain terverifikasi)</span></label>
                        <input class="field-input" id="email_from" type="text"
                               value="{{ $emailCfg?->config['from_address'] ?? '' }}" placeholder="alerts@yourdomain.com">
                    </div>
                    <div>
                        <label class="field-label">Send Test To</label>
                        <input class="field-input" id="email_to" type="text"
                               value="{{ $emailCfg?->config['to_address'] ?? '' }}" placeholder="admin@yourdomain.com">
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn-reset" data-type="email">Reset</button>
                <div style="display:flex;gap:8px;">
                    <button class="btn-primary" style="background:#475569;" data-test="email">
                        <span class="material-symbols-outlined" style="font-size:14px;">send</span>
                        Test
                    </button>
                    <button class="btn-primary" data-save="email">
                        <span class="material-symbols-outlined" style="font-size:14px;">save</span>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Threshold Rules ══ --}}
    <div class="section-header" style="margin-top:8px;">
        <div>
            <h2 class="section-title">Threshold Rules</h2>
            <p style="font-family:'Inter',sans-serif;font-size:13px;color:#94A3B8;margin:4px 0 0;">
                Automated alert conditions triggered by network metrics
            </p>
        </div>
        <button class="btn-add-rule" id="addRuleBtn">
            <span class="material-symbols-outlined" style="font-size:15px;">add</span>
            Add Rule
        </button>
    </div>

    {{-- Toolbar: search + filter chips --}}
    <div class="rules-toolbar">
        <div class="rules-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input class="rules-search" id="rulesSearch" type="text" placeholder="Search rules…">
        </div>
        <button class="filter-chip active" data-filter="all">All</button>
        <button class="filter-chip" data-filter="critical">Critical</button>
        <button class="filter-chip" data-filter="warning">Warning</button>
        <button class="filter-chip" data-filter="info">Info</button>
        <button class="filter-chip" data-filter="active">Active</button>
        <button class="filter-chip" data-filter="inactive">Inactive</button>
    </div>

    {{-- Rules grid --}}
    <div class="rules-grid" id="rulesGrid">
        @forelse($thresholdRules as $rule)
        <div class="rule-card {{ $rule->is_active ? '' : 'disabled' }}"
             id="rule-card-{{ $rule->id }}"
             data-id="{{ $rule->id }}"
             data-severity="{{ $rule->severity }}"
             data-active="{{ $rule->is_active ? '1' : '0' }}"
             data-search="{{ strtolower($rule->title . ' ' . $rule->conditionLabel()) }}">

            <div class="rule-card-top">
                <span class="severity-badge {{ $rule->severity }}">{{ strtoupper($rule->severity) }}</span>
                <label class="toggle-wrap rule-toggle" title="{{ $rule->is_active ? 'Disable' : 'Enable' }}">
                    <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }}
                           onchange="toggleRule({{ $rule->id }}, this)">
                    <span class="toggle-track"></span>
                </label>
            </div>

            <p class="rule-title">{{ $rule->title }}</p>
            <p class="rule-desc">{{ $rule->conditionLabel() }}</p>

            <div class="rule-meta">
                @if($rule->last_triggered_at)
                <div class="rule-meta-item">
                    <span class="material-symbols-outlined">schedule</span>
                    {{ $rule->last_triggered_at->diffForHumans() }}
                </div>
                @else
                <div class="rule-meta-item">
                    <span class="material-symbols-outlined">schedule</span>
                    Never triggered
                </div>
                @endif
                <div class="rule-meta-item">
                    <span class="material-symbols-outlined">bolt</span>
                    {{ $rule->trigger_count }}x today
                </div>
            </div>

            <div class="rule-footer">
                <div class="rule-channels">
                    @if(is_array($rule->channels) && in_array('telegram', $rule->channels))
                        <span class="material-symbols-outlined" title="Telegram" style="color:#2AABEE;font-size:16px;">send</span>
                    @endif
                    @if(is_array($rule->channels) && in_array('email', $rule->channels))
                        <span class="material-symbols-outlined" title="Email" style="color:#F59E0B;font-size:16px;">mail</span>
                    @endif
                </div>
                <div class="rule-actions">
                    <button class="icon-btn" title="Duplicate" onclick="duplicateRule({{ $rule->id }})">
                        <span class="material-symbols-outlined">content_copy</span>
                    </button>
                    <button class="icon-btn" title="Edit" onclick="openEditDrawer({{ $rule->id }})">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="icon-btn danger" title="Delete" onclick="confirmDelete({{ $rule->id }})">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#94A3B8;" id="emptyRules">
            No rules yet. Click <strong>+ Add Rule</strong> to create one.
        </div>
        @endforelse
    </div>

    {{-- ══ Alert History ══ --}}
    <div class="history-section">
        <div class="section-header">
            <h2 class="section-title">Alert History</h2>
            <button class="btn-view-all" id="openHistoryPanelBtn">
                View All
                <span class="material-symbols-outlined" style="font-size:15px;">arrow_forward</span>
            </button>
        </div>

        <div class="history-card">
            <div class="filters-bar">
                <div class="search-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input class="search-input" id="historySearch" type="text" placeholder="Search alerts…">
                </div>
                <select class="filter-select" id="historyStatusFilter">
                    <option value="">Status: All</option>
                    <option value="sent">Sent</option>
                    <option value="failed">Failed</option>
                </select>
                <select class="filter-select" id="historyChannelFilter">
                    <option value="">Channel: All</option>
                    <option value="telegram">Telegram</option>
                    <option value="email">Email</option>
                </select>
                <input class="date-input" type="date" id="historyDateFilter">
                <div class="export-group">
                    <button class="btn-export" id="exportHistoryCsvBtn">Export .CSV</button>
                    <button class="btn-export" id="exportHistoryPdfBtn">Export .PDF</button>
                </div>
            </div>

            <table class="history-table" id="historyTable">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Channel</th>
                        <th>Recipient</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                    @forelse($alertHistory as $log)
                    <tr data-status="{{ $log->status }}"
                        data-channel="{{ $log->channel }}"
                        data-date="{{ $log->sent_at->format('Y-m-d') }}"
                        data-search="{{ strtolower($log->recipient . ' ' . $log->message) }}">
                        <td class="mono">{{ $log->sent_at->format('d M Y, H:i') }}</td>
                        <td>
                            <div class="channel-cell">
                                @if($log->channel === 'telegram')
                                    <span class="channel-icon telegram material-symbols-outlined">send</span> Telegram
                                @else
                                    <span class="channel-icon email material-symbols-outlined">mail</span> Email
                                @endif
                            </div>
                        </td>
                        <td><span class="mono">{{ $log->recipient }}</span></td>
                        <td>
                            <span class="status-badge {{ $log->status }}">
                                <span class="material-symbols-outlined">{{ $log->status === 'sent' ? 'check_circle' : 'cancel' }}</span>
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td>{{ $log->message }}</td>
                    </tr>
                    @empty
                    <tr id="noHistoryRow"><td colspan="5" style="text-align:center;padding:32px;color:#94A3B8;">No alert history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="page-footer">
        <span class="footer-copy">© 2026 NetPulse – Network Operations Center</span>
        <div class="footer-status">
            <div class="status-item"><span class="status-dot"></span> API Status: Operational</div>
            <div class="status-item"><span class="status-dot"></span> Database: Live</div>
            <a href="#" class="footer-link">Privacy Policy</a>
            <a href="#" class="footer-link">System Logs</a>
        </div>
    </div>

</main>

{{-- ══ Rule data for JS ══ --}}
<script>
const RULES_DATA = @json($thresholdRules->keyBy('id'));
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const BASE = '';

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span><span>${msg}</span>`;
    c.appendChild(t);
    requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, 3000);
}

// ── Eye toggle ───────────────────────────────────────────────────────────────
document.querySelectorAll('.field-eye-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const inp = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector('.material-symbols-outlined');
        if (inp.type === 'password') { inp.type = 'text'; icon.textContent = 'visibility_off'; }
        else                         { inp.type = 'password'; icon.textContent = 'visibility'; }
    });
});

// ── Save channel ─────────────────────────────────────────────────────────────
document.querySelectorAll('[data-save]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const type = btn.dataset.save;
        let config = {};
        if (type === 'telegram') {
            config = { token: document.getElementById('tg_token').value, chat_id: document.getElementById('tg_chat_id').value };
        } else {
            config = {
                    host:         document.getElementById('email_host').value,
                    port:         document.getElementById('email_port').value,
                    username:     document.getElementById('email_username').value,
                    password:     document.getElementById('email_password').value,
                    from_address: document.getElementById('email_from').value,
                    to_address:   document.getElementById('email_to').value,
                };
        }
        const is_active = document.getElementById(type === 'telegram' ? 'tg_active' : 'email_active').checked;
        btn.disabled = true;
        try {
            const r = await fetch('/alert/channel/save', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify({ type, config, is_active }) });
            const d = await r.json();
            showToast(d.message, d.ok ? 'success' : 'error');
        } catch(e) { showToast('Network error.', 'error'); }
        btn.disabled = false;
    });
});

// ── Test channel ─────────────────────────────────────────────────────────────
document.querySelectorAll('[data-test]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const type = btn.dataset.test;
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:14px;animation:spin 1s linear infinite;">progress_activity</span> Testing…';

        // Kirim config langsung dari form (tidak perlu save dulu)
        let config = {};
        if (type === 'telegram') {
            config = {
                token:   document.getElementById('tg_token').value.trim(),
                chat_id: document.getElementById('tg_chat_id').value.trim(),
            };
        } else {
            config = {
                host:         document.getElementById('email_host').value.trim(),
                port:         document.getElementById('email_port').value.trim(),
                username:     document.getElementById('email_username').value.trim(),
                password:     document.getElementById('email_password').value.trim(),
                from_address: document.getElementById('email_from').value.trim(),
                to_address:   document.getElementById('email_to').value.trim(),
            };
        }

        try {
            const r = await fetch('/alert/channel/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type, config }),
            });
            const d = await r.json();
            showToast(d.message, d.ok ? 'success' : 'error');
            // Reload halaman setelah 1.5 detik agar history table ikut update
            if (d.ok) setTimeout(() => location.reload(), 1500);
        } catch(e) { showToast('Network error — tidak bisa menghubungi server.', 'error'); }
        btn.disabled = false; btn.innerHTML = orig;
    });
});

// ── Reset channel ────────────────────────────────────────────────────────────
document.querySelectorAll('[data-reset], .btn-reset').forEach(btn => {
    btn.addEventListener('click', () => {
        const type = btn.dataset.type;
        if (!type) return;
        if (type === 'telegram') {
            document.getElementById('tg_token').value = '';
            document.getElementById('tg_chat_id').value = '';
            document.getElementById('tg_active').checked = false;
        } else {
            ['email_host','email_port','email_username','email_password','email_from','email_to'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('email_active').checked = false;
        }
        showToast(`${type} settings cleared.`);
    });
});

// ── Drawer open/close ────────────────────────────────────────────────────────
const drawer        = document.getElementById('ruleDrawer');
const drawerOverlay = document.getElementById('drawerOverlay');

function openDrawer() { drawer.classList.add('open'); drawerOverlay.classList.add('open'); document.body.style.overflow='hidden'; }
function closeDrawer(){ drawer.classList.remove('open'); drawerOverlay.classList.remove('open'); document.body.style.overflow=''; }

document.getElementById('addRuleBtn').addEventListener('click', () => {
    document.getElementById('drawerTitle').textContent = 'Add Alert Rule';
    document.getElementById('editRuleId').value = '';
    document.getElementById('d_title').value = '';
    document.getElementById('d_target_device').value = '';
    document.getElementById('d_metric_type').value = 'latency';
    document.getElementById('d_condition').value = 'gt';
    document.getElementById('d_threshold_value').value = '';
    document.getElementById('d_duration').value = '5m';
    document.getElementById('d_is_active').checked = true;
    setDrawerSeverity('warning');
    setDrawerChannels([]);
    openDrawer();
});
document.getElementById('drawerCloseBtn').addEventListener('click', closeDrawer);
document.getElementById('drawerCancelBtn').addEventListener('click', closeDrawer);
drawerOverlay.addEventListener('click', closeDrawer);

function setDrawerSeverity(val) {
    document.querySelectorAll('.severity-radio-label').forEach(lbl => {
        lbl.classList.toggle('selected', lbl.dataset.val === val);
        lbl.querySelector('input').checked = lbl.dataset.val === val;
    });
}
document.querySelectorAll('.severity-radio-label').forEach(lbl => {
    lbl.addEventListener('click', () => setDrawerSeverity(lbl.dataset.val));
});
function setDrawerChannels(arr) {
    ['telegram','email'].forEach(ch => {
        const lbl = document.getElementById(`chk_${ch}`);
        const inp = lbl.querySelector('input');
        inp.checked = arr.includes(ch);
        lbl.classList.toggle('checked', arr.includes(ch));
    });
}
['telegram','email'].forEach(ch => {
    const lbl = document.getElementById(`chk_${ch}`);
    lbl.addEventListener('click', () => {
        const inp = lbl.querySelector('input');
        inp.checked = !inp.checked;
        lbl.classList.toggle('checked', inp.checked);
    });
});

function openEditDrawer(id) {
    const rule = RULES_DATA[id];
    if (!rule) return;
    document.getElementById('drawerTitle').textContent = 'Edit Alert Rule';
    document.getElementById('editRuleId').value = id;
    document.getElementById('d_title').value = rule.title;
    document.getElementById('d_target_device').value = rule.target_device || '';
    document.getElementById('d_metric_type').value = rule.metric_type || 'latency';
    document.getElementById('d_condition').value = rule.condition || 'gt';
    document.getElementById('d_threshold_value').value = rule.threshold_value || '';
    document.getElementById('d_duration').value = rule.duration || '5m';
    document.getElementById('d_is_active').checked = !!rule.is_active;
    setDrawerSeverity(rule.severity);
    setDrawerChannels(rule.channels || []);
    openDrawer();
}

// ── Save rule ─────────────────────────────────────────────────────────────────
document.getElementById('drawerSaveBtn').addEventListener('click', async () => {
    const id = document.getElementById('editRuleId').value;
    const channels = ['telegram','email'].filter(ch => document.getElementById(`chk_${ch}`).querySelector('input').checked);
    const severity = document.querySelector('.severity-radio-label.selected')?.dataset.val || 'warning';
    const body = {
        title:           document.getElementById('d_title').value.trim(),
        target_device:   document.getElementById('d_target_device').value.trim(),
        metric_type:     document.getElementById('d_metric_type').value,
        condition:       document.getElementById('d_condition').value,
        threshold_value: document.getElementById('d_threshold_value').value,
        duration:        document.getElementById('d_duration').value,
        severity,
        channels,
        is_active:       document.getElementById('d_is_active').checked,
    };
    if (!body.title) { showToast('Rule name is required.','error'); return; }
    if (!channels.length) { showToast('Select at least one notification channel.','error'); return; }

    const btn = document.getElementById('drawerSaveBtn');
    btn.disabled = true;
    try {
        const url    = id ? `/alert/rules/${id}` : '/alert/rules';
        const method = id ? 'PUT' : 'POST';
        const r = await fetch(url, { method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body: JSON.stringify(body) });
        const d = await r.json();
        if (d.ok) {
            showToast(d.message);
            closeDrawer();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(d.message || 'Save failed.','error');
        }
    } catch(e) { showToast('Network error.','error'); }
    btn.disabled = false;
});

// ── Toggle rule ───────────────────────────────────────────────────────────────
async function toggleRule(id, checkbox) {
    try {
        const r = await fetch(`/alert/rules/${id}/toggle`, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF} });
        const d = await r.json();
        if (d.ok) {
            const card = document.getElementById(`rule-card-${id}`);
            card.dataset.active = d.is_active ? '1' : '0';
            card.classList.toggle('disabled', !d.is_active);
            showToast(`Rule ${d.is_active ? 'enabled' : 'disabled'}.`);
        }
    } catch(e) { showToast('Toggle failed.','error'); checkbox.checked = !checkbox.checked; }
}

// ── Delete rule ───────────────────────────────────────────────────────────────
let pendingDeleteId = null;
const deleteModal = document.getElementById('deleteModal');
function confirmDelete(id) { pendingDeleteId = id; deleteModal.classList.add('open'); }
document.getElementById('deleteCancelBtn').addEventListener('click', () => { deleteModal.classList.remove('open'); pendingDeleteId = null; });
document.getElementById('deleteConfirmBtn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    deleteModal.classList.remove('open');
    try {
        const r = await fetch(`/alert/rules/${pendingDeleteId}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF} });
        const d = await r.json();
        if (d.ok) {
            document.getElementById(`rule-card-${pendingDeleteId}`)?.remove();
            showToast('Rule deleted.');
        }
    } catch(e) { showToast('Delete failed.','error'); }
    pendingDeleteId = null;
});

// ── Duplicate rule ────────────────────────────────────────────────────────────
async function duplicateRule(id) {
    try {
        const r = await fetch(`/alert/rules/${id}/duplicate`, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF} });
        const d = await r.json();
        if (d.ok) { showToast(d.message); setTimeout(() => location.reload(), 600); }
    } catch(e) { showToast('Duplicate failed.','error'); }
}

// ── Filter + Search rules ─────────────────────────────────────────────────────
let currentFilter = 'all';
const rulesSearch = document.getElementById('rulesSearch');
function applyRulesFilter() {
    const term = rulesSearch.value.toLowerCase();
    document.querySelectorAll('.rule-card[data-id]').forEach(card => {
        const matchFilter =
            currentFilter === 'all'     ? true :
            currentFilter === 'active'  ? card.dataset.active === '1' :
            currentFilter === 'inactive'? card.dataset.active === '0' :
            card.dataset.severity === currentFilter;
        const matchSearch = !term || (card.dataset.search || '').includes(term);
        card.style.display = (matchFilter && matchSearch) ? '' : 'none';
    });
}
document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentFilter = chip.dataset.filter;
        applyRulesFilter();
    });
});
rulesSearch.addEventListener('input', applyRulesFilter);

// ── History filter (main table) ───────────────────────────────────────────────
function filterHistoryTable(tableId, searchId, statusId, channelId, dateId) {
    const term    = document.getElementById(searchId)?.value.toLowerCase()  || '';
    const status  = document.getElementById(statusId)?.value  || '';
    const channel = document.getElementById(channelId)?.value || '';
    const date    = document.getElementById(dateId)?.value    || '';
    document.querySelectorAll(`#${tableId} tbody tr[data-status]`).forEach(row => {
        const ok =
            (!term    || (row.dataset.search  || '').includes(term)) &&
            (!status  || row.dataset.status  === status) &&
            (!channel || row.dataset.channel === channel) &&
            (!date    || row.dataset.date    === date);
        row.style.display = ok ? '' : 'none';
    });
}
['historySearch','historyStatusFilter','historyChannelFilter','historyDateFilter'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () =>
        filterHistoryTable('historyTable','historySearch','historyStatusFilter','historyChannelFilter','historyDateFilter'));
});

// ── Panel filters ─────────────────────────────────────────────────────────────
['panelSearch','panelStatusFilter','panelChannelFilter','panelDateFilter'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        const term    = document.getElementById('panelSearch').value.toLowerCase();
        const status  = document.getElementById('panelStatusFilter').value;
        const channel = document.getElementById('panelChannelFilter').value;
        const date    = document.getElementById('panelDateFilter').value;
        document.querySelectorAll('#panelHistoryBody tr[data-status]').forEach(row => {
            const ok =
                (!term    || (row.dataset.search||'').includes(term)) &&
                (!status  || row.dataset.status  === status) &&
                (!channel || row.dataset.channel === channel) &&
                (!date    || row.dataset.date    === date);
            row.style.display = ok ? '' : 'none';
        });
    });
});

// ── History slide panel open/close ────────────────────────────────────────────
const histPanel        = document.getElementById('historyPanel');
const histPanelOverlay = document.getElementById('historyPanelOverlay');
document.getElementById('openHistoryPanelBtn').addEventListener('click', () => {
    histPanel.classList.add('open'); histPanelOverlay.classList.add('open'); document.body.style.overflow = 'hidden';
});
document.getElementById('historyPanelCloseBtn').addEventListener('click', () => {
    histPanel.classList.remove('open'); histPanelOverlay.classList.remove('open'); document.body.style.overflow = '';
});
histPanelOverlay.addEventListener('click', () => {
    histPanel.classList.remove('open'); histPanelOverlay.classList.remove('open'); document.body.style.overflow = '';
});

// ── Export CSV helper ─────────────────────────────────────────────────────────
function exportTableCSV(tbodySelector, filename) {
    const headers = ['Time','Channel','Recipient','Status','Message'];
    const rows    = document.querySelectorAll(tbodySelector + ' tr[data-status]:not([style*="none"])');
    const lines   = [headers.join(',')];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const line  = Array.from(cells).map(td => `"${td.textContent.trim().replace(/"/g,'""')}"`);
        lines.push(line.join(','));
    });
    const blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = filename; a.click(); URL.revokeObjectURL(a.href);
}
document.getElementById('exportHistoryCsvBtn').addEventListener('click', () =>
    exportTableCSV('#historyTable tbody', 'alert-history-' + new Date().toISOString().slice(0,10) + '.csv'));
document.getElementById('panelExportCsvBtn').addEventListener('click', () =>
    exportTableCSV('#panelHistoryBody', 'alert-history-full-' + new Date().toISOString().slice(0,10) + '.csv'));

// ── Export PDF (print panel) ──────────────────────────────────────────────────
function exportPDF(tbodySelector) {
    const rows = document.querySelectorAll(tbodySelector + ' tr[data-status]:not([style*="none"])');
    let html = `<html><head><title>Alert History</title><style>
        body{font-family:sans-serif;padding:24px}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th{background:#064E3B;color:#fff;padding:8px 12px;text-align:left}
        td{padding:8px 12px;border-bottom:1px solid #eee}
        tr:nth-child(even) td{background:#f8fafc}
    </style></head><body>
    <h2 style="margin-bottom:16px">Alert History — NetPulse</h2>
    <table><thead><tr><th>Time</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Message</th></tr></thead><tbody>`;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        html += '<tr>' + Array.from(cells).map(td => `<td>${td.textContent.trim()}</td>`).join('') + '</tr>';
    });
    html += '</tbody></table></body></html>';
    const w = window.open('','_blank'); w.document.write(html); w.document.close();
    setTimeout(() => { w.print(); }, 500);
}
document.getElementById('exportHistoryPdfBtn').addEventListener('click', () => exportPDF('#historyTable tbody'));
document.getElementById('panelExportPdfBtn').addEventListener('click',   () => exportPDF('#panelHistoryBody'));

// ── Spin animation for test button ────────────────────────────────────────────
const style = document.createElement('style');
style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(style);
</script>
</body>
</html>
