<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>NetPulse | Network Operations Center</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <script type="application/ld+json">
    {
        "@context": {
            "schema":    "https://schema.org/",
            "netpulse":  "http://netpulse.local/ontology#",
            "xsd":       "http://www.w3.org/2001/XMLSchema#"
        },
        "@type": "schema:WebPage",
        "@id": "{{ url('/dashboard') }}",
        "schema:name": "NetPulse — Network Operations Center Dashboard",
        "schema:description": "Real-time overview of network health, active incidents, device availability, and latency metrics.",
        "schema:url": "{{ url('/dashboard') }}",
        "schema:about": {
            "@type": "schema:SoftwareApplication",
            "schema:name": "NetPulse",
            "schema:applicationCategory": "Network Monitoring",
            "schema:operatingSystem": "Linux, Windows Server",
            "schema:description": "Network Operations Center application that monitors routers, switches, and servers via SNMP and ICMP."
        },
        "schema:mainEntity": {
            "@type": "schema:Dataset",
            "schema:name": "Network Status Summary",
            "schema:description": "Live aggregated status of {{ $totalDevices }} monitored network devices.",
            "schema:variableMeasured": [
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "System Health Score",
                    "schema:value": {{ $healthScore }},
                    "schema:unitCode": "P1"
                },
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "Total Monitored Devices",
                    "schema:value": {{ $totalDevices }}
                },
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "Online Devices",
                    "schema:value": {{ $upDevices }}
                },
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "Offline Devices",
                    "schema:value": {{ $downDevices }}
                },
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "Average Latency",
                    "schema:value": {{ $avgLatency ?? 0 }},
                    "schema:unitCode": "MSC"
                },
                {
                    "@type": "schema:PropertyValue",
                    "schema:name": "Active Incidents",
                    "schema:value": {{ $activeIncidents->count() }}
                }
            ],
            "netpulse:healthStatus": "{{ $healthScore >= 80 ? 'good' : ($healthScore >= 50 ? 'warning' : 'critical') }}",
            "schema:dateModified": {
                "@type": "xsd:dateTime",
                "@value": "{{ now()->toIso8601String() }}"
            },
            "schema:sameAs": "{{ url('/api/rdf/devices') }}"
        }
    }
    </script>
</head>

<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main-content">

<section class="hero-section flex justify-between items-end fade-in-up" style="margin-bottom:32px;">
            <div>
                <h1 class="hero-title">Dashboard</h1>
                <!-- <div class="hero-sub">
                    <span class="flex items-center gap-2" id="refresh-indicator">
                        <span class="status-dot green pulse-dot"></span>
                        Auto-refresh in 60s
                    </span>
                </div> -->
            </div>
            <div class="health-panel">
                <span class="health-label">System Health</span>
                <span class="health-value">{{ $healthScore >= 80 ? "GOOD" : ($healthScore >= 50 ? "WARNING" : "CRITICAL") }} ({{ $healthScore }}%)</span>
                <div class="health-bar">
                    <div class="health-fill" style="width:{{ $healthScore }}%;"></div>
                </div>
            </div>
        </section>

<div class="main-grid flex gap-6" style="display:grid;grid-template-columns:1fr 300px;align-items:start;">

<div class="flex flex-col gap-6">

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
                            <p class="incidents-sub">
                                @if($activeIncidents->count() === 0)
                                    All systems operational
                                @else
                                    {{ $activeIncidents->count() }} active incident{{ $activeIncidents->count() !== 1 ? 's' : '' }} require{{ $activeIncidents->count() === 1 ? 's' : '' }} attention
                                @endif
                            </p>
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

                    <div class="flex flex-col gap-3 relative z-1" style="max-height:276px;overflow-y:auto;padding-right:4px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.15) transparent;">
                        @forelse($activeIncidents as $incident)
                            @php
                                // Map status (Critical/Warning/Monitoring/Info) → severity class
                                $severity  = match($incident->status) {
                                    'Critical'   => 'critical',
                                    'Warning'    => 'major',
                                    'Monitoring' => 'normal',
                                    default      => 'info',
                                };
                                $iconName  = $incident->status === 'Critical' ? 'priority_high' : 'warning';
                                $badgeClass = $incident->status === 'Critical' ? 'badge-critical' : 'badge-major';
                            @endphp
                            <div class="incident-row">
                                <div class="flex items-center gap-4">
                                    <div class="incident-icon {{ $severity }}">
                                        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">{{ $iconName }}</span>
                                    </div>
                                    <div class="incident-info">
                                        <div class="incident-meta">
                                            <span class="incident-id mono">#INC-{{ str_pad($incident->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            <span class="badge {{ $badgeClass }}">{{ $incident->status }}</span>
                                        </div>
                                        <p class="incident-desc">{{ $incident->issue }}</p>
                                        <p class="incident-time">{{ $incident->device }}@if($incident->ip_address) · {{ $incident->ip_address }}@endif • Active for {{ $incident->displayDuration() }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('device.show', $incident->device) }}" class="investigate-btn">Investigate</a>
                            </div>
                        @empty
                            <p style="color:#94a3b8; font-size:0.9375rem; text-align:center; padding:20px;">No active incidents — all systems operational.</p>
                        @endforelse
                    </div>
                </div>

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
                                $corePoints = $latencyCore ?? array_fill(0, 21, 0);
                                $edgePoints = $latencyEdge ?? array_fill(0, 21, 0);
                                $xStep = 1000 / max(count($corePoints) - 1, 1);
                                $pathCore = '';
                                $pathEdge = '';
                                foreach ($corePoints as $i => $ms) {
                                    $x = $i * $xStep;
                                    $y = 200 - min(200, max(0, $ms)); // invert: 0ms=bottom, 200ms=top
                                    $pathCore .= ($i === 0 ? "M" : "L") . round($x,1) . ',' . round($y,1) . ' ';
                                }
                                foreach ($edgePoints as $i => $ms) {
                                    $x = $i * $xStep;
                                    $y = 200 - min(200, max(0, $ms));
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

<div class="flex flex-col gap-4">

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
                        @if($unknownDevices > 0)
                        <p style="font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;color:#b45309;margin:5px 0 0;display:flex;align-items:center;gap:4px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;display:inline-block;flex-shrink:0;"></span>
                            +{{ $unknownDevices }} unknown
                        </p>
                        @endif
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Requests</p>
                        <p class="stat-number-lg mono">{{ number_format($avgLatency, 1) }} ms</p>
                    </div>
                </div>

<div class="card sla-card fade-in-up">
                    <div class="sla-header">
                        <div>
                            <p class="sla-title">Rolling SLA</p>
                            <p class="sla-value mono">{{ $healthScore }}%</p>
                        </div>
                        @if($healthScore >= 80)
                            <span class="badge badge-secure">Secure</span>
                        @elseif($healthScore >= 50)
                            <span class="badge badge-sla-warning">Warning</span>
                        @else
                            <span class="badge badge-sla-critical">Critical</span>
                        @endif
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

<div class="card-muted activity-feed fade-in-up">
                    <h4 class="activity-title">Activity Feed</h4>
                    <div class="flex flex-col gap-4">
                        @forelse($latestDevices->take(5) as $act)
                        @php $actStatus = $act->effectiveStatus(); $actUp = $actStatus === 'up'; @endphp
                        <div class="activity-item">
                            <span class="status-dot {{ $actStatus === 'up' ? 'green' : ($actStatus === 'unknown' ? 'gray' : 'red') }} activity-dot"></span>
                            <div>
                                <p class="activity-text {{ $actUp ? '' : 'critical' }}">
                                    {{ $act->device }} — {{ strtoupper($actStatus) }}
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
                    <!-- <button class="view-log-btn">View Logs</button> -->
                    <button class="view-log-btn" onclick="window.location.href='logs'">
                        View Logs
                    </button>
                </div>
            </div>
        </div>

<section class="card device-table-section fade-in-up">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Prioritized Device Inventory</h2>
                    <p class="section-sub">Sorted by criticality and operational status</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('dashboard.export.csv') }}" class="export-btn" style="text-decoration:none;">
                        <span class="material-symbols-outlined" style="font-size:14px;vertical-align:-2px;">download</span>
                        Export .CSV
                    </a>
                    
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
                        $devEff   = $dev->effectiveStatus();
                        $isUp     = $devEff === 'up';
                        $isUnknown = $devEff === 'unknown';
                        $rowClass = $isUp ? '' : ($isUnknown ? 'row-warning' : 'row-critical');
                        $icon     = str_contains(strtolower($dev->device), 'switch') ? 'lan' : 'router';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>
                            <div class="device-icon-cell">
                                <div class="device-icon {{ $isUp ? 'core' : ($isUnknown ? 'warning' : 'critical') }}">
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
                            @if($isUnknown)<br><small style="color:#f59e0b;">&#9888; Stale</small>@endif
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
                            @if($isUnknown)
                            <div class="status-badge" style="background:#fef3c7;color:#92400e;border-color:#fcd34d;">
                                <span class="status-dot" style="background:#f59e0b;"></span>
                                UNKNOWN
                            </div>
                            @else
                            <div class="status-badge {{ $isUp ? 'up' : 'down' }}">
                                <span class="status-dot {{ $isUp ? 'green' : 'ping-ring' }}" style="{{ $isUp ? '' : 'background:#dc2626;' }}"></span>
                                {{ strtoupper($devEff) }}
                            </div>
                            @endif
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
                <button id="viewAllNodesBtn" class="view-all-link">View All Managed Nodes</button>
            </div>
        </section>

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

<div id="monthly-bars" class="perf-bars-container" style="display:none;">
                @php
                    $monthChunks = collect($monthlyData)->chunk(6);
                @endphp
                @foreach($monthChunks as $chunk)
                @php
                    $chunkArr    = $chunk->values();
                    $avgPct      = round($chunkArr->avg('pct'));
                    $chunkType   = $avgPct >= 80 ? 'green' : ($avgPct >= 50 ? 'orange' : 'red');
                    // Label: ambil tanggal awal–akhir chunk, misal "May 01" → "1"
                    $firstParts  = explode(' ', $chunkArr->first()['label']);
                    $lastParts   = explode(' ', $chunkArr->last()['label']);
                    $monthAbbr   = $firstParts[0];
                    $dayStart    = (int) ($firstParts[1] ?? 1);
                    $dayEnd      = (int) ($lastParts[1]  ?? $dayStart);
                    $rangeLabel  = $dayStart . '–' . $dayEnd;
                    $fullLabel   = $monthAbbr . ' ' . $rangeLabel;
                @endphp
                <div class="perf-bar-item" title="{{ $fullLabel }} — avg {{ $avgPct }}% uptime">
                    <div style="width:100%;flex:1;display:flex;align-items:flex-end;">
                        <div class="perf-bar {{ $chunkType }}" style="height:{{ max($avgPct, 2) }}%;"></div>
                    </div>
                    <p class="perf-label {{ $chunkType === 'red' ? 'red' : '' }}">{{ $rangeLabel }}</p>
                </div>
                @endforeach
            </div>
        </section>

        @include('partials.footer')
    </main>

<div class="overlay" id="panelOverlay"></div>
    <div id="allNodesSlidePanel" class="slide-panel">
        <div class="slide-panel-header">
            <h3>All Managed Nodes</h3>
            <button class="close-btn" id="closePanelBtn">&times;</button>
        </div>
        <div class="slide-panel-content">
            @forelse($allDevices as $device)
                @php $deviceEff = $device->effectiveStatus(); @endphp
                <div class="device-row">
                    <span class="name">{{ $device->device }}</span>
                    <span class="flex items-center gap-2">
                        <span class="status-dot {{ $deviceEff === 'up' ? 'green' : ($deviceEff === 'unknown' ? 'gray' : 'red') }}"></span>
                        {{ strtoupper($deviceEff) }}
                    </span>
                </div>
            @empty
                <p style="color:var(--text-muted);">No devices found.</p>
            @endforelse
        </div>
    </div>

    <script>
        // (function() {
        //     const INTERVAL = 60; // detik
        //     let remaining = INTERVAL;
        //     const loadedAt = Date.now();

        //     const indicator = document.getElementById('refresh-indicator');
        //     const lastUpdated = document.getElementById('last-updated-text');

        //     // Tampilkan kapan halaman terakhir di-load
        //     function updateLastUpdated() {
        //         const secs = Math.round((Date.now() - loadedAt) / 1000);
        //         if (secs < 5)       lastUpdated.textContent = 'Just updated';
        //         else if (secs < 60) lastUpdated.textContent = secs + 's ago';
        //         else                lastUpdated.textContent = Math.round(secs / 60) + 'm ago';
        //     }

        //     function tick() {
        //         remaining--;
        //         if (remaining <= 0) {
        //             location.reload();
        //             return;
        //         }
        //         indicator.innerHTML =
        //             '<span class="status-dot green pulse-dot"></span> Auto-refresh in ' + remaining + 's';
        //         updateLastUpdated();
        //         setTimeout(tick, 1000);
        //     }
        //     setTimeout(tick, 1000);
        // })();

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