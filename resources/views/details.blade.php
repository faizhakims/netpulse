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
                        <span class="device-status-badge {{ $status && strtolower($status->status) === 'up' ? '' : 'offline' }}">
                            <span class="dot"></span>
                            {{ $status ? strtoupper($status->status) : 'UNKNOWN' }}
                        </span>
                    </div>
                </div>

                <div class="device-hero-actions">
                    <button class="btn-action btn-edit">
                        <span class="material-symbols-outlined">edit</span>
                        Edit
                    </button>
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
                    <div class="stat-card-value up">{{ $status ? strtoupper($status->status) : 'N/A' }}</div>
                    <div class="stat-card-sub">Last checked: {{ $status && $status->checked_at ? $status->checked_at->diffForHumans() : '-' }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Latency</div>
                    <div class="stat-card-value good">
                        {{ $status->latency_ms ?? '-' }}<span class="unit">{{ $status && $status->latency_ms ? ' ms' : '' }}</span>
                    </div>
                    <div class="stat-card-sub green">Optimal range</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Uptime</div>
                    <div class="stat-card-value good">
                        {{ $uptimePct }}<span class="unit">%</span>
                    </div>
                    <div class="stat-card-sub">Last 30 days</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Packet Loss</div>
                    <div class="stat-card-value">
                        {{ $metrics->get('packetLoss') ? $metrics->get('packetLoss')->metric_value : '0' }}<span class="unit">%</span>
                    </div>
                    <div class="stat-card-sub">Stable connection</div>
                </div>
            </div>

            {{-- ===================== Real-time Latency Chart ===================== --}}
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

            {{-- ===================== Incident History + Device Info ===================== --}}
            <div class="two-col-section">

                {{-- Incident History --}}
                <div class="incident-card">
                    <div class="card-header-row">
                        <h2 class="card-title">Incident History</h2>
                        <a href="#" class="card-view-all">View All</a>
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
                            {{-- Replace with @foreach($incidents as $incident) when DB is ready --}}
                            <tr>
                                <td>Today, 10:24 AM</td>
                                <td>
                                    <span class="incident-tag tag-yellow">
                                        <span class="dot dot-yellow"></span>
                                        Latency Spike
                                    </span>
                                </td>
                                <td>2m 14s</td>
                            </tr>
                            <tr>
                                <td>Yesterday, 14:00</td>
                                <td>
                                    <span class="incident-tag tag-red">
                                        <span class="dot dot-red"></span>
                                        Connection Lost
                                    </span>
                                </td>
                                <td>45s</td>
                            </tr>
                            <tr>
                                <td>Yesterday, 09:12</td>
                                <td>
                                    <span class="incident-tag tag-orange">
                                        <span class="dot dot-orange"></span>
                                        High Packet Loss
                                    </span>
                                </td>
                                <td>3m 20s</td>
                            </tr>
                            <tr>
                                <td>Mar 14, 16:30</td>
                                <td>
                                    <span class="incident-tag tag-yellow">
                                        <span class="dot dot-yellow"></span>
                                        Interface Flapping
                                    </span>
                                </td>
                                <td>18s</td>
                            </tr>
                            <tr>
                                <td>Mar 12, 08:15</td>
                                <td>
                                    <span class="incident-tag tag-orange">
                                        <span class="dot dot-orange"></span>
                                        Packet Loss &gt; 5%
                                    </span>
                                </td>
                                <td>1m 05s</td>
                            </tr>
                            {{-- @endforeach --}}
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
                                {{ $metrics->get('sysUpTime') ? $metrics->get('sysUpTime')->metric_value . ' ticks' : '-' }}
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

                    {{-- Alert Settings --}}
                    <div>
                        <div class="alert-settings-title">Alert Settings</div>
                        <div class="alert-toggles">
                            <label class="alert-toggle-item">
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-track"></span>
                                </label>
                                <span class="alert-icon">
                                    <span class="material-symbols-outlined">send</span>
                                    Telegram
                                </span>
                            </label>
                            <label class="alert-toggle-item">
                                <label class="toggle-switch">
                                    <input type="checkbox" >
                                    <span class="toggle-track"></span>
                                </label>
                                <span class="alert-icon">
                                    <span class="material-symbols-outlined">mail</span>
                                    Email
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== Log Activity ===================== --}}
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
                        <span class="log-time">{{ $log->checked_at ? $log->checked_at->format('H:i A') : '-' }}</span>
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

    {{-- Chart.js & Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {

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

            /* ── Ping Button ── */
            const pingBtn = document.getElementById('pingBtn');
            if (pingBtn) {
                pingBtn.addEventListener('click', function () {
                    const icon = this.querySelector('.material-symbols-outlined');
                    const orig = icon.textContent;
                    icon.textContent = 'sync';
                    icon.style.animation = 'spin 0.8s linear infinite';
                    setTimeout(() => {
                        icon.textContent = orig;
                        icon.style.animation = '';
                    }, 1800);
                });
            }

            /* ── Reboot Button Long Press (3 detik) ── */
            const rebootBtn = document.getElementById('rebootBtn');
            if (rebootBtn) {
                let pressTimer = null;
                let progressInterval = null;
                const HOLD_DURATION = 3000; // 3 detik

                function clearRebootTimers() {
                    if (pressTimer) clearTimeout(pressTimer);
                    if (progressInterval) clearInterval(progressInterval);
                    pressTimer = null;
                    progressInterval = null;
                }

                function startRebootHold() {
                    // Bersihkan timer sebelumnya
                    clearRebootTimers();
                    
                    // Tambahkan kelas untuk memulai transisi CSS
                    rebootBtn.classList.add('rebooting');
                    
                    // Set timeout utama untuk eksekusi reboot
                    pressTimer = setTimeout(() => {
                        // Lakukan aksi reboot di sini (misal kirim request)
                        alert('Device reboot initiated!'); // Ganti dengan fetch/axios POST
                        // Reset tampilan
                        rebootBtn.classList.remove('rebooting');
                        clearRebootTimers();
                    }, HOLD_DURATION);
                }

                function cancelRebootHold() {
                    if (rebootBtn.classList.contains('rebooting')) {
                        rebootBtn.classList.remove('rebooting');
                    }
                    clearRebootTimers();
                }

                // Event untuk mouse / touch
                rebootBtn.addEventListener('mousedown', (e) => {
                    // Hanya trigger jika klik kiri
                    if (e.button !== 0) return;
                    startRebootHold();
                });

                rebootBtn.addEventListener('mouseup', cancelRebootHold);
                rebootBtn.addEventListener('mouseleave', cancelRebootHold);
                
                // Touch events untuk mobile
                rebootBtn.addEventListener('touchstart', (e) => {
                    e.preventDefault(); // mencegah scroll atau zoom
                    startRebootHold();
                }, { passive: false });
                
                rebootBtn.addEventListener('touchend', cancelRebootHold);
                rebootBtn.addEventListener('touchcancel', cancelRebootHold);
            }

            /* ── Log Activity Filters ── */
            const filterBtns = document.querySelectorAll('#logFilters .log-filter-btn');
            const logItems   = document.querySelectorAll('#logList .log-item');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    // Toggle active state
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const filter = this.dataset.filter;

                    logItems.forEach(item => {
                        if (filter === 'all' || item.dataset.type === filter) {
                            item.classList.remove('hidden');
                        } else {
                            item.classList.add('hidden');
                        }
                    });
                });
            });

            /* ── Export CSV (dummy) ── */
            document.getElementById('exportCsvBtn').addEventListener('click', function (e) {
                e.preventDefault();

                // Collect visible log rows
                const rows = [['Time', 'Event', 'Description']];
                document.querySelectorAll('#logList .log-item:not(.hidden)').forEach(item => {
                    const time  = item.querySelector('.log-time').textContent.trim();
                    const title = item.querySelector('.log-event-title').textContent.trim();
                    const desc  = item.querySelector('.log-event-desc').textContent.trim();
                    rows.push([time, title, desc]);
                });

                const csv = rows.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href     = url;
                a.download = 'log_activity.csv';
                a.click();
                URL.revokeObjectURL(url);
            });

        })();
    </script>
</body>
</html>