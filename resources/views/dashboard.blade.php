<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>NetPulse | Network Operations Center</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- CSS Terpisah --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main-content">

        {{-- Hero Header --}}
        <section class="hero-section flex justify-between items-end fade-in-up" style="margin-bottom:32px;">
            <div>
                <h1 class="hero-title">Dashboard</h1>
                <div class="hero-sub">
                    <span class="flex items-center gap-2">
                        <span class="status-dot green pulse-dot"></span>
                        Auto-refresh in 8s
                    </span>
                    <span class="hero-divider"></span>
                    <span>Last updated: 2s ago</span>
                </div>
            </div>
            <div class="health-panel">
                <span class="health-label">System Health</span>
                <span class="health-value">{{ $healthScore >= 80 ? "GOOD" : ($healthScore >= 50 ? "WARNING" : "CRITICAL") }} ({{ $healthScore }}%)</span>
                <div class="health-bar">
                    <div class="health-fill" style="width:{{ $healthScore }}%;"></div>
                </div>
            </div>
        </section>

        {{-- 2-col layout --}}
        <div class="main-grid flex gap-6" style="display:grid;grid-template-columns:1fr 300px;align-items:start;">

            {{-- LEFT COLUMN --}}
            <div class="flex flex-col gap-6">

                {{-- Active Incidents --}}
                <div class="incidents-panel fade-in-up">
                    <div class="incidents-bg-icon">
                        <span class="material-symbols-outlined" style="font-size:160px;color:white;font-variation-settings:'FILL' 1;">emergency_home</span>
                    </div>

                    <div class="incidents-header">
                        <div>
                            <h2 class="incidents-title">
                                <span class="status-dot ping-ring" style="background:#ef4444;width:10px;height:10px;"></span>
                                Active Incidents
                            </h2>
                            <p class="incidents-sub">Immediate attention required for {{ $activeIncidents->count() }} critical issues</p>
                        </div>
                        <div class="incidents-stats">
                            <div class="stat-block">
                                <p class="stat-label">Total Active</p>
                                <p class="stat-number mono">{{ $activeIncidents->count() }}</p>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-severity">
                                <p class="stat-severity-label">Max Severity</p>
                                <p class="stat-severity-value mono">{{ strtoupper($maxSeverity ?? 'NONE') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 relative z-1">
                        @forelse($activeIncidents as $incident)
                            @php
                                $severity = strtolower($incident->severity ?? 'major');
                                $iconName = $severity === 'critical' ? 'priority_high' : 'warning';
                                $badgeClass = $severity === 'critical' ? 'badge-critical' : 'badge-major';
                            @endphp
                            <div class="incident-row">
                                <div class="flex items-center gap-4">
                                    <div class="incident-icon {{ $severity }}">
                                        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">{{ $iconName }}</span>
                                    </div>
                                    <div class="incident-info">
                                        <div class="incident-meta">
                                            <span class="incident-id mono">#INC-{{ $incident->id }}</span>
                                            <span class="badge {{ $badgeClass }}">{{ ucfirst($severity) }}</span>
                                        </div>
                                        <p class="incident-desc">{{ $incident->description }}</p>
                                        <p class="incident-time">{{ $incident->location ?? '' }} • Active for {{ $incident->duration ?? '' }}</p>
                                    </div>
                                </div>
                                <a href="{{ $incident->device ? route('device.show', $incident->device) : '#' }}" class="investigate-btn">Investigate</a>
                            </div>
                        @empty
                            <p style="color:#94a3b8; font-size:0.9375rem; text-align:center; padding:20px;">No Incidents</p>
                        @endforelse
                    </div>
                </div>

                {{-- Global Network Latency --}}
                <div class="card fade-in-up" style="padding:28px;">
                    <div class="flex justify-between items-center" style="margin-bottom:24px;">
                        <h3 style="font-size:1.0625rem;font-weight:700;margin:0;">Global Network Latency</h3>
                        <div class="flex gap-5">
                            <div class="flex items-center gap-2">
                                <span style="width:10px;height:10px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981;"></span>
                                <span style="font-size:0.75rem;font-weight:500;color:var(--text-muted);">Core Network</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span style="width:10px;height:10px;border-radius:50%;background:#3b82f6;box-shadow:0 0 8px #3b82f6;"></span>
                                <span style="font-size:0.75rem;font-weight:500;color:var(--text-muted);">Edge Layer</span>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="threshold-line">
                            <span class="threshold-label">ALERT THRESHOLD (100ms)</span>
                        </div>
                        <svg viewBox="0 0 1000 200" preserveAspectRatio="none" class="w-full h-full">
                            <defs>
                                <linearGradient id="grad-core" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.28"/>
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                </linearGradient>
                                <linearGradient id="grad-edge" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            @php
                                $corePoints = $latencyCore ?? array_fill(0, 21, 100);
                                $edgePoints = $latencyEdge ?? array_fill(0, 21, 80);
                                $xStep = 1000 / (count($corePoints) - 1);
                                $pathCore = '';
                                $pathEdge = '';
                                foreach ($corePoints as $i => $y) {
                                    $x = $i * $xStep;
                                    $pathCore .= ($i === 0 ? "M" : "L") . round($x,1) . ',' . round($y,1) . ' ';
                                }
                                foreach ($edgePoints as $i => $y) {
                                    $x = $i * $xStep;
                                    $pathEdge .= ($i === 0 ? "M" : "L") . round($x,1) . ',' . round($y,1) . ' ';
                                }
                                $fillCore = $pathCore . 'L1000,200 L0,200 Z';
                                $fillEdge = $pathEdge . 'L1000,200 L0,200 Z';
                            @endphp
                            <path d="{{ $fillCore }}" fill="url(#grad-core)"/>
                            <path class="chart-glow-green" d="{{ $pathCore }}" fill="none" stroke="#10b981" stroke-width="2.5"/>
                            <path d="{{ $fillEdge }}" fill="url(#grad-edge)"/>
                            <path class="chart-glow-blue" d="{{ $pathEdge }}" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                        </svg>
                    </div>

                    <div class="latency-stats">
                        <div>
                            <p class="latency-label">Core Infrastructure</p>
                            <p class="latency-value mono">{{ $coreAvgLatency }} <span class="latency-unit">ms</span></p>
                        </div>
                        <div>
                            <p class="latency-label">Edge Layer GW</p>
                            <p class="latency-value mono">{{ $edgeAvgLatency }} <span class="latency-unit">ms</span></p>
                        </div>
                        <div>
                            <p class="latency-label">Peak Latency</p>
                            <p class="latency-value mono">{{ $peakLatency }} <span class="latency-unit">ms</span></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="flex flex-col gap-4">

                {{-- Stat cards 2×2 --}}
                <div class="stat-grid fade-in-up">
                    <div class="card stat-card">
                        <p class="stat-label">Live Nodes</p>
                        <p class="stat-number-lg mono">{{ $totalDevices }}</p>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Healthy</p>
                        <p class="stat-number-lg text-green mono">{{ $upDevices }}</p>
                    </div>
                    <div class="card stat-card offline">
                        <p class="stat-label" style="color:var(--red);">Offline</p>
                        <p class="stat-number-lg text-red mono">{{ $downDevices }}</p>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Requests</p>
                        <p class="stat-number-lg mono">{{ number_format($avgLatency, 1) }} ms</p>
                    </div>
                </div>

                {{-- SLA Card --}}
                <div class="card sla-card fade-in-up">
                    <div class="sla-header">
                        <div>
                            <p class="sla-title">Rolling SLA</p>
                            <p class="sla-value mono">{{ $healthScore }}%</p>
                        </div>
                        <span class="badge badge-secure">Secure</span>
                    </div>
                    <div class="sla-bars">
                        <div class="sla-bar" style="height:60%;"></div>
                        <div class="sla-bar" style="height:68%;"></div>
                        <div class="sla-bar" style="height:55%;"></div>
                        <div class="sla-bar" style="height:78%;"></div>
                        <div class="sla-bar" style="height:70%;"></div>
                        <div class="sla-bar" style="height:85%;"></div>
                        <div class="sla-bar" style="height:75%;"></div>
                        <div class="sla-bar" style="height:90%;"></div>
                        <div class="sla-bar highlight" style="height:100%;"></div>
                    </div>
                </div>

                {{-- Activity Feed --}}
                <div class="card-muted activity-feed fade-in-up">
                    <h4 class="activity-title">Activity Feed</h4>
                    <div class="flex flex-col gap-4">
                        @forelse($latestDevices->take(5) as $act)
                        @php $actUp = strtolower($act->status) === 'up'; @endphp
                        <div class="activity-item">
                            <span class="status-dot {{ $actUp ? 'green' : 'red' }} activity-dot"></span>
                            <div>
                                <p class="activity-text {{ $actUp ? '' : 'critical' }}">
                                    {{ $act->device }} — {{ strtoupper($act->status) }}
                                </p>
                                <p class="activity-time">
                                    {{ $act->checked_at ? $act->checked_at->diffForHumans() : '-' }}
                                </p>
                            </div>
                        </div>
                        @empty
                        <p style="color:#94A3B8;font-size:13px;">No recent activity.</p>
                        @endforelse
                    </div>
                    <button class="view-log-btn">View Logs</button>
                </div>
            </div>
        </div>

        {{-- Device Inventory --}}
        <section class="card device-table-section fade-in-up">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Prioritized Device Inventory</h2>
                    <p class="section-sub">Sorted by criticality and operational status</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('dashboard.export.csv') }}" class="btn-outline">Export .CSV</a>
                    {{-- Tombol Add Device dihapus --}}
                </div>
            </div>

            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Node Identity</th>
                        <th>Layer</th>
                        <th>Uptime</th>
                        <th>Load</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestDevices as $dev)
                    @php
                        $isUp     = strtolower($dev->status) === 'up';
                        $rowClass = $isUp ? '' : 'row-critical';
                        $icon     = str_contains(strtolower($dev->device), 'switch') ? 'lan' : 'router';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>
                            <div class="device-icon-cell">
                                <div class="device-icon {{ $isUp ? 'core' : 'critical' }}">
                                    <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1;">{{ $icon }}</span>
                                </div>
                                <div>
                                    <p class="device-name {{ $isUp ? '' : 'critical' }} mono">{{ $dev->device }}</p>
                                    <p class="device-desc {{ $isUp ? '' : 'critical' }}">{{ $dev->ip_address }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="cell-layer {{ $isUp ? '' : 'critical' }}">Network Device</td>
                        <td class="cell-uptime {{ $isUp ? '' : 'critical' }} mono">
                            {{ $dev->checked_at ? $dev->checked_at->diffForHumans() : '-' }}
                        </td>
                        <td>
                            <div class="load-cell">
                                @php $lat = min($dev->latency_ms ?? 0, 200); $pct = round(($lat/200)*100); @endphp
                                <div class="progress-bar">
                                    <div class="progress-fill {{ $pct > 70 ? 'warning' : '' }}" style="width:{{ $pct }}%;"></div>
                                </div>
                                <span class="load-value">{{ $dev->latency_ms ? $dev->latency_ms . ' ms' : '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="status-badge {{ $isUp ? 'up' : 'down' }}">
                                <span class="status-dot {{ $isUp ? 'green' : 'ping-ring' }}" style="{{ $isUp ? '' : 'background:#dc2626;' }}"></span>
                                {{ strtoupper($dev->status) }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#94A3B8;">
                            No device data found. Make sure the monitoring agent is running.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="table-footer">
                <button id="viewAllNodesBtn" class="view-all-link">View All Managed Nodes →</button>
            </div>
        </section>

        {{-- Performance History --}}
        <section class="card-muted perf-section fade-in-up">
            <div class="perf-header">
                <div>
                    <h2 class="perf-title">Performance History</h2>
                    <p class="perf-sub" id="perf-sub-text">Global region health aggregation (Last 7 Days)</p>
                </div>
                <div class="perf-tabs">
                    <button class="perf-tab active" id="tab-weekly">Weekly</button>
                    <button class="perf-tab" id="tab-monthly">Monthly</button>
                </div>
            </div>

            {{-- Data Mingguan --}}
            <div id="weekly-bars" class="perf-bars-container">
                @foreach ($weeklyChartData as $day)
                <div class="perf-bar-item">
                    <div style="width:100%;flex:1;display:flex;align-items:flex-end;">
                        <div class="perf-bar {{ $day['type'] }}" style="height:{{ $day['h'] ?: '2%' }};"></div>
                    </div>
                    <p class="perf-label {{ $day['type']==='red' ? 'red' : '' }}">{{ $day['label'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Data Bulanan (hidden by default) --}}
            <div id="monthly-bars" class="perf-bars-container" style="display:none;">
                @foreach($monthlyData as $day)
                <div class="perf-bar-item">
                    <div style="width:100%;flex:1;display:flex;align-items:flex-end;">
                        <div class="perf-bar {{ $day['type'] }}" style="height:{{ $day['h'] ?: '2%' }};"></div>
                    </div>
                    <p class="perf-label {{ $day['type']==='red' ? 'red' : '' }}">{{ $day['label'] }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Footer --}}
        <footer class="dashboard-footer">
            <span>© 2026 NetPulse – Network Operations Center</span>
            <div class="footer-links">
                <span class="flex items-center gap-2">
                    <span class="status-dot" style="background:#10b981;"></span> API Status: Operational
                </span>
                <span class="flex items-center gap-2">
                    <span class="status-dot" style="background:#10b981;"></span> Database: 4ms Sync
                </span>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </footer>
    </main>

    {{-- SLIDE PANEL UNTUK ALL MANAGED NODES --}}
    <div class="overlay" id="panelOverlay"></div>
    <div id="allNodesSlidePanel" class="slide-panel">
        <div class="slide-panel-header">
            <h3>All Managed Nodes</h3>
            <button class="close-btn" id="closePanelBtn">&times;</button>
        </div>
        <div class="slide-panel-content">
            @forelse($allDevices as $device)
                @php $status = strtolower($device->status); @endphp
                <div class="device-row">
                    <span class="name">{{ $device->device }}</span>
                    <span class="flex items-center gap-2">
                        <span class="status-dot {{ $status === 'up' ? 'green' : 'red' }}"></span>
                        {{ strtoupper($device->status) }}
                    </span>
                </div>
            @empty
                <p style="color:var(--text-muted);">No devices found.</p>
            @endforelse
        </div>
    </div>

    <script>
        // Fade-in animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, i * 60);
                }
            });
        }, { threshold: 0.08 });
        document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

        // Slide panel logic
        const viewAllBtn = document.getElementById('viewAllNodesBtn');
        const panel = document.getElementById('allNodesSlidePanel');
        const overlay = document.getElementById('panelOverlay');
        const closeBtn = document.getElementById('closePanelBtn');

        function openPanel() {
            panel.classList.add('active');
            overlay.classList.add('active');
        }
        function closePanel() {
            panel.classList.remove('active');
            overlay.classList.remove('active');
        }

        viewAllBtn.addEventListener('click', openPanel);
        closeBtn.addEventListener('click', closePanel);
        overlay.addEventListener('click', closePanel);

        // Performance tab switch
        const tabWeekly = document.getElementById('tab-weekly');
        const tabMonthly = document.getElementById('tab-monthly');
        const weeklyBars = document.getElementById('weekly-bars');
        const monthlyBars = document.getElementById('monthly-bars');
        const perfSubText = document.getElementById('perf-sub-text');

        tabWeekly.addEventListener('click', () => {
            tabWeekly.classList.add('active');
            tabMonthly.classList.remove('active');
            weeklyBars.style.display = 'flex';
            monthlyBars.style.display = 'none';
            perfSubText.textContent = 'Global region health aggregation (Last 7 Days)';
        });

        tabMonthly.addEventListener('click', () => {
            tabMonthly.classList.add('active');
            tabWeekly.classList.remove('active');
            monthlyBars.style.display = 'flex';
            weeklyBars.style.display = 'none';
            perfSubText.textContent = 'Global region health aggregation (Last 30 Days)';
        });
    </script>
</body>
</html>