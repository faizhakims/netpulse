<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse - Device Details</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/details.css') }}">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f5f7f9;
            font-family: 'DM Sans', sans-serif;
            color: #2c2f31;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="device-detail-page">
        <div class="device-detail-canvas">

            {{-- ===================== Hero Header ===================== --}}
            <div class="device-hero">
                <div class="device-hero-meta">
                    <h1 class="device-hero-name">{{ $deviceName }}</h1>
                    <div class="device-hero-sub">
                        <span class="device-hero-monitor">
                            IP: {{ $status->ip_address ?? '-' }}
                            &nbsp;|&nbsp;
                            Last checked: {{ $status && $status->checked_at ? $status->checked_at->diffForHumans() : 'N/A' }}
                        </span>
                        {{-- REVISI 1: Status UNKNOWN ⚠ dihapus — hanya tampil UP atau DOWN --}}
                        @if($effectiveStatus !== 'unknown')
                        <span class="device-status-badge {{ $effectiveStatus === 'up' ? '' : 'offline' }}">
                            <span class="dot"></span>
                            {{ strtoupper($effectiveStatus) }}
                        </span>
                        @endif
                    </div>
                </div>

                <div class="device-hero-actions">
                    {{-- REVISI 6: Edit, Ping, Reboot, Hapus tidak diubah fungsionalitasnya --}}
                    <button class="btn-action btn-ping" id="pingBtn">
                        <span class="material-symbols-outlined">wifi_tethering</span>
                        Ping Now
                    </button>
                    <button class="btn-action btn-restart" id="rebootBtn">
                        <span class="material-symbols-outlined">restart_alt</span>
                        <span class="reboot-text">Reboot Device</span>
                        <span class="reboot-progress"></span>
                    </button>
                    <button class="btn-action btn-delete" title="Delete device">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>

            {{-- ===================== Stat Cards ===================== --}}
            <div class="stat-cards-row">
                <div class="stat-card">
                    <div class="stat-card-label">Status</div>
                    <div class="stat-card-value {{ $effectiveStatus === 'up' ? 'up' : ($effectiveStatus === 'unknown' ? 'unknown' : 'down') }}">
                        {{ strtoupper($effectiveStatus) }}
                    </div>
                    <div class="stat-card-sub">
                        Last checked: {{ $status && $status->checked_at ? $status->checked_at->diffForHumans() : '-' }}
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Latency</div>
                    <div class="stat-card-value good">
                        @if($effectiveStatus === 'unknown')
                            -
                        @else
                            {{ $status->latency_ms ?? '-' }}<span class="unit">{{ $status && $status->latency_ms ? ' ms' : '' }}</span>
                        @endif
                    </div>
                    <div class="stat-card-sub green">Optimal range</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Uptime</div>
                    <div class="stat-card-value good">
                        @if($effectiveStatus === 'unknown')
                            -
                        @else
                            {{ $uptimePct }}<span class="unit">%</span>
                        @endif
                    </div>
                    <div class="stat-card-sub">Last 30 days</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Packet Loss</div>
                    <div class="stat-card-value">
                        @if($effectiveStatus === 'unknown')
                            -
                        @else
                            {{ $metrics->get('packet_loss') ? $metrics->get('packet_loss')->metric_value : '0' }}<span class="unit">%</span>
                        @endif
                    </div>
                    <div class="stat-card-sub">Stable connection</div>
                </div>
            </div>

            {{-- ===================== Real-time Latency Chart ===================== --}}
            @if($effectiveStatus !== 'unknown')
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h2 class="chart-card-title">Real-time Latency</h2>
                        <p class="chart-card-subtitle">Ping history over the last hour</p>
                    </div>
                    <div class="chart-stats">
                        <div class="chart-stat">
                            <span class="chart-stat-label">Avg</span>
                            <span class="chart-stat-value avg">{{ $latencyAvg !== null ? $latencyAvg . ' ms' : 'N/A' }}</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">Peak</span>
                            <span class="chart-stat-value peak">{{ $latencyPeak !== null ? $latencyPeak . ' ms' : 'N/A' }}</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">Min</span>
                            <span class="chart-stat-value min">{{ $latencyMin !== null ? $latencyMin . ' ms' : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="latencyChart"></canvas>
                </div>
            </div>
            @endif

            {{-- ===================== Incident History + Device Info ===================== --}}
            <div class="two-col-section">

                {{-- REVISI 2: Incident History — terhubung ke DB, tampil 5 terakhir --}}
                <div class="incident-card">
                    <div class="card-header-row">
                        <h2 class="card-title">Incident History</h2>
                        {{-- View All membuka slider panel --}}
                        <a href="#" class="card-view-all" id="viewAllIncidentsBtn">View All</a>
                    </div>
                    <table class="incident-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Issue</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incidents->take(5) as $incident)
                            @php
                                $tagClass = match(strtolower($incident->status)) {
                                    'critical'   => 'tag-red',
                                    'warning'    => 'tag-yellow',
                                    'monitoring' => 'tag-orange',
                                    default      => 'tag-blue',
                                };
                                $dotClass = match(strtolower($incident->status)) {
                                    'critical'   => 'dot-red',
                                    'warning'    => 'dot-yellow',
                                    'monitoring' => 'dot-orange',
                                    default      => 'dot-blue',
                                };
                            @endphp
                            <tr>
                                <td>{{ $incident->started_at ? $incident->started_at->format('M d, H:i') : '-' }}</td>
                                <td>
                                    <span class="incident-tag {{ $tagClass }}">
                                        <span class="dot {{ $dotClass }}"></span>
                                        {{ $incident->issue }}
                                    </span>
                                </td>
                                <td>{{ $incident->displayDuration() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" style="text-align:center; color: var(--text-muted); padding: 24px 0; font-size:13px;">
                                    No incidents recorded for this device.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Device Information --}}
                <div class="device-info-card">
                    <div class="card-header-row">
                        <h2 class="card-title">Device Information</h2>
                    </div>

                    <div class="device-info-grid">
                        <div class="device-info-field">
                            <div class="device-info-field-label">Hostname</div>
                            <div class="device-info-field-value">{{ $deviceName }}</div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">IP Address</div>
                            <div class="device-info-field-value">{{ $status->ip_address ?? '-' }}</div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">MAC Address</div>
                            <div class="device-info-field-value">{{ $metrics->get('macAddress') ? $metrics->get('macAddress')->metric_value : '-' }}</div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Type</div>
                            <div class="device-info-field-value">
                                <span class="material-symbols-outlined">router</span>
                                {{ $metrics->get('sysDescr') ? 'Router' : 'Network Device' }}
                            </div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Vendor</div>
                            <div class="device-info-field-value">{{ $metrics->get('sysDescr') ? $metrics->get('sysDescr')->metric_value : '-' }}</div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Last Reboot</div>
                            <div class="device-info-field-value">
                                {{ $lastReboot ?? '-' }}
                            </div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Location</div>
                            <div class="device-info-field-value">
                                <span class="material-symbols-outlined">location_on</span>
                                -
                            </div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Added at</div>
                            <div class="device-info-field-value">
                                {{ $status && $status->checked_at ? $status->checked_at->format('d-m-Y') : '-' }}
                            </div>
                        </div>
                        <div class="device-info-field">
                            <div class="device-info-field-label">Monitoring Interval</div>
                            <div class="device-info-field-value">
                                {{ $metrics->get('monitoringInterval') ? $metrics->get('monitoringInterval')->metric_value : '10 Seconds' }}
                            </div>
                        </div>
                    </div>

                    {{-- REVISI 3: Alert Settings — toggle terhubung ke DB via AJAX --}}
                    <div>
                        <div class="alert-settings-title">Alert Settings</div>
                        <div class="alert-toggles">
                            <label class="alert-toggle-item">
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        id="alertToggleTelegram"
                                        data-channel="telegram"
                                        {{ $alertChannels['telegram'] ?? false ? 'checked' : '' }}>
                                    <span class="toggle-track"></span>
                                </label>
                                <span class="alert-icon">
                                    <span class="material-symbols-outlined">send</span>
                                    Telegram
                                </span>
                            </label>
                            <label class="alert-toggle-item">
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        id="alertToggleEmail"
                                        data-channel="email"
                                        {{ $alertChannels['email'] ?? false ? 'checked' : '' }}>
                                    <span class="toggle-track"></span>
                                </label>
                                <span class="alert-icon">
                                    <span class="material-symbols-outlined">mail</span>
                                    Email
                                </span>
                            </label>
                        </div>
                        <div id="alertSaveMsg" class="alert-save-msg" style="display:none;"></div>
                    </div>
                </div>
            </div>

            {{-- ===================== Log Activity ===================== --}}
            {{-- REVISI 4: Tampilkan 6 log, jika lebih bisa di-scroll dalam card --}}
            <div class="log-card">
                <div class="log-card-header">
                    <h2 class="card-title">Log Activity</h2>
                    <div class="log-card-actions">
                        <a href="#" class="btn-export" id="exportCsvBtn">
                            <span class="material-symbols-outlined">download</span>
                            Export .CSV
                        </a>
                        <div class="log-filters" id="logFilters">
                            <button class="log-filter-btn active" data-filter="all">ALL</button>
                            <span class="log-filter-sep">|</span>
                            <button class="log-filter-btn" data-filter="warning">WARNING</button>
                            <span class="log-filter-sep">|</span>
                            <button class="log-filter-btn" data-filter="critical">CRITICAL</button>
                            <span class="log-filter-sep">|</span>
                            <button class="log-filter-btn" data-filter="info">INFO</button>
                        </div>
                    </div>
                </div>

                {{-- REVISI 4: log-list dibatasi tingginya, bisa scroll internal --}}
                <div class="log-list" id="logList">
                    @forelse($statusHistory as $log)
                    @php
                        $isUp      = strtolower($log->status) === 'up';
                        $logType   = $isUp ? 'info' : 'critical';
                        $dotColor  = $isUp ? 'green' : 'red';
                        $title     = $isUp ? 'Ping success' : 'Device unreachable';
                        $desc      = $isUp
                            ? 'Response time: ' . ($log->latency_ms ?? '-') . ' ms. Device reachable.'
                            : 'Device did not respond to ping. Status: DOWN.';
                    @endphp
                    <div class="log-item" data-type="{{ $logType }}">
                        <span class="log-time">{{ $log->checked_at ? $log->checked_at->format('H:i') : '-' }}</span>
                        <div class="log-indicator"><span class="log-dot {{ $dotColor }}"></span></div>
                        <div class="log-content">
                            <div class="log-event-title">{{ $title }}</div>
                            <div class="log-event-desc">{{ $desc }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="log-item" data-type="info">
                        <div class="log-content">
                            <div class="log-event-title">No logs found</div>
                            <div class="log-event-desc">No status history available for this device.</div>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- ===================== Footer ===================== --}}
            <footer class="page-footer">
                <span class="footer-copy">&copy; 2026 NetPulse &mdash; Network Operations Center</span>
                <div class="footer-status-group">
                    <span class="footer-status-item">
                        <span class="dot"></span>
                        API Status: Operational
                    </span>
                    <span class="footer-status-item">
                        <span class="dot"></span>
                        Database: 4ms Sync
                    </span>
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">System Logs</a>
                </div>
            </footer>

        </div>
    </div>

    {{-- ===================== REVISI 2: Incident History Slider Panel ===================== --}}
    <div class="incident-slider-overlay" id="incidentSliderOverlay"></div>
    <div class="incident-slider-panel" id="incidentSliderPanel">
        <div class="incident-slider-header">
            <div>
                <h2 class="incident-slider-title">All Incidents</h2>
                <p class="incident-slider-sub">{{ $deviceName }}</p>
            </div>
            <button class="incident-slider-close" id="incidentSliderClose">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="incident-slider-body">
            @if($incidents->count() === 0)
                <div class="incident-slider-empty">
                    <span class="material-symbols-outlined" style="font-size:40px; color:var(--border)">check_circle</span>
                    <p>No incidents recorded for this device.</p>
                </div>
            @else
            <table class="incident-table incident-table-full">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incidents as $incident)
                    @php
                        $tagClass = match(strtolower($incident->status)) {
                            'critical'   => 'tag-red',
                            'warning'    => 'tag-yellow',
                            'monitoring' => 'tag-orange',
                            default      => 'tag-blue',
                        };
                        $dotClass = match(strtolower($incident->status)) {
                            'critical'   => 'dot-red',
                            'warning'    => 'dot-yellow',
                            'monitoring' => 'dot-orange',
                            default      => 'dot-blue',
                        };
                    @endphp
                    <tr>
                        <td>{{ $incident->started_at ? $incident->started_at->format('d M Y, H:i') : '-' }}</td>
                        <td>
                            <span class="incident-tag {{ $tagClass }}">
                                <span class="dot {{ $dotClass }}"></span>
                                {{ $incident->issue }}
                            </span>
                        </td>
                        <td>
                            <span class="incident-status-badge {{ $incident->isActive() ? 'badge-active' : 'badge-resolved' }}">
                                {{ $incident->isActive() ? 'Active' : 'Resolved' }}
                            </span>
                        </td>
                        <td>{{ $incident->displayDuration() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Chart.js & Scripts --}}
    <script>
        // window.DEVICE_NAME = "{{ $deviceName }}";
        window.DEVICE_NAME = @json($deviceName);
        window.CSRF_TOKEN  = "{{ csrf_token() }}";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {

            @if($effectiveStatus !== 'unknown')
            /* ── Latency Chart ── */
            const ctx = document.getElementById('latencyChart').getContext('2d');

            const labels = {!! json_encode($latencyLabels) !!};
            const data   = {!! json_encode($latencyData) !!};

            const gradient = ctx.createLinearGradient(0, 0, 0, 180);
            gradient.addColorStop(0,   'rgba(0,105,71,0.30)');
            gradient.addColorStop(0.7, 'rgba(0,105,71,0.06)');
            gradient.addColorStop(1,   'rgba(0,105,71,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data,
                        fill: true,
                        backgroundColor: gradient,
                        borderColor: '#006947',
                        borderWidth: 2,
                        pointRadius: data.map((v, i) => i === data.indexOf(Math.max(...data)) ? 5 : 0),
                        pointBackgroundColor: '#B31B25',
                        tension: 0.45,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.parsed.y} ms`
                            }
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                maxTicksLimit: 7,
                                font: { family: 'Inter', size: 11 },
                                color: '#595c5e',
                            }
                        },
                        y: {
                            min: 0,
                            max: Math.max(100, ...data) + 20,
                            grid: { color: '#f0f2f4' },
                            border: { display: false, dash: [4, 4] },
                            ticks: {
                                stepSize: 50,
                                font: { family: 'Inter', size: 11 },
                                color: '#595c5e',
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'thresholdLine',
                    afterDraw(chart) {
                        const { ctx, scales: { x, y } } = chart;
                        const yPos = y.getPixelForValue(50);
                        ctx.save();
                        ctx.beginPath();
                        ctx.setLineDash([6, 4]);
                        ctx.strokeStyle = '#EF4444';
                        ctx.lineWidth = 1.2;
                        ctx.moveTo(x.left, yPos);
                        ctx.lineTo(x.right, yPos);
                        ctx.stroke();
                        ctx.setLineDash([]);
                        ctx.font = '11px Inter';
                        ctx.fillStyle = '#EF4444';
                        ctx.textAlign = 'right';
                        ctx.fillText('Threshold 50ms', x.right, yPos - 5);
                        ctx.restore();
                    }
                }]
            });
            @endif

            /* ── REVISI 4: Log Activity Filters + scroll container tetap --*/
            const filterBtns = document.querySelectorAll('#logFilters .log-filter-btn');
            const logList    = document.getElementById('logList');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.dataset.filter;
                    logList.querySelectorAll('.log-item').forEach(item => {
                        if (filter === 'all' || item.dataset.type === filter) {
                            item.classList.remove('hidden');
                        } else {
                            item.classList.add('hidden');
                        }
                    });
                });
            });

            /* ── Export CSV ── */
            document.getElementById('exportCsvBtn').addEventListener('click', function (e) {
                e.preventDefault();
                const rows = [['Time', 'Event', 'Description']];
                logList.querySelectorAll('.log-item:not(.hidden)').forEach(item => {
                    const time  = item.querySelector('.log-time')?.textContent.trim() ?? '';
                    const title = item.querySelector('.log-event-title')?.textContent.trim() ?? '';
                    const desc  = item.querySelector('.log-event-desc')?.textContent.trim() ?? '';
                    rows.push([time, title, desc]);
                });
                const csv  = rows.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href = url; a.download = 'log_activity.csv'; a.click();
                URL.revokeObjectURL(url);
            });

            /* ── REVISI 2: Incident Slider Panel ── */
            const overlay  = document.getElementById('incidentSliderOverlay');
            const panel    = document.getElementById('incidentSliderPanel');
            const openBtn  = document.getElementById('viewAllIncidentsBtn');
            const closeBtn = document.getElementById('incidentSliderClose');

            function openSlider() {
                overlay.classList.add('active');
                panel.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSlider() {
                overlay.classList.remove('active');
                panel.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (openBtn)  openBtn.addEventListener('click',  e => { e.preventDefault(); openSlider(); });
            if (closeBtn) closeBtn.addEventListener('click', closeSlider);
            if (overlay)  overlay.addEventListener('click',  closeSlider);

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeSlider();
            });

            /* ── REVISI 3: Alert Settings Toggle — save ke DB via AJAX ── */
            document.querySelectorAll('.alert-toggle-item input[data-channel]').forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const channel  = this.dataset.channel;
                    const isActive = this.checked;
                    const msgEl    = document.getElementById('alertSaveMsg');

                    fetch('/alert/channel/save', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.CSRF_TOKEN,
                        },
                        body: JSON.stringify({
                            type:      channel,
                            is_active: isActive,
                            config:    {},
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (msgEl) {
                            msgEl.textContent = data.message ?? (isActive ? channel + ' alerts enabled.' : channel + ' alerts disabled.');
                            msgEl.className   = 'alert-save-msg ' + (data.ok ? 'success' : 'error');
                            msgEl.style.display = 'block';
                            setTimeout(() => { msgEl.style.display = 'none'; }, 2500);
                        }
                    })
                    .catch(() => {
                        if (msgEl) {
                            msgEl.textContent = 'Failed to save. Please try again.';
                            msgEl.className   = 'alert-save-msg error';
                            msgEl.style.display = 'block';
                            setTimeout(() => { msgEl.style.display = 'none'; }, 2500);
                        }
                    });
                });
            });

        })();
    </script>
    <script src="{{ asset('js/device-actions.js') }}"></script>
</body>
</html>
