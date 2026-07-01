<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse - Traffic Monitoring</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;700&family=Liberation+Mono&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/traffic.css') }}">
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="main" id="mainContent">

        <div class="page-header">
            <div>
                <h1 class="page-title">Traffic Monitoring</h1>
                <p class="page-subtitle">Real-time analysis of network</p>
            </div>
        </div>

        <div class="hero-row">
            <div class="hero-card">
                <div class="hero-card-top">
                    <div class="hero-card-header">
                        <span class="hero-label">TOTAL 24H BANDWIDTH</span>
                        <div class="hero-value-row">
                            <span class="hero-value">{{ \App\Models\InterfaceTraffic::formatBytes($totalBytes) }}</span>
                            <span class="hero-unit"></span>
                        </div>
                    </div>
                    <div class="hero-breakdown">
                        <div class="breakdown-item">
                            <div class="breakdown-header">
                                <span class="breakdown-dot upload-dot"></span>
                                <span class="breakdown-label">Upload</span>
                            </div>
                            <span class="breakdown-value">{{ \App\Models\InterfaceTraffic::formatBytes($totalIn) }}</span>
                        </div>
                        <div class="breakdown-item">
                            <div class="breakdown-header">
                                <span class="breakdown-dot download-dot"></span>
                                <span class="breakdown-label">Download</span>
                            </div>
                            <span class="breakdown-value">{{ \App\Models\InterfaceTraffic::formatBytes($totalOut) }}</span>
                        </div>
                    </div>
                </div>

                <div class="chart-container" style="position:relative;">
                    <canvas id="bandwidthChart" style="width:100%;height:192px;display:block;"></canvas>
                </div>

            </div>

            <div class="stats-column">
                <div class="stat-card latency-card">
                    <div class="stat-card-header-row">
                        <span class="stat-card-label">NETWORK LATENCY</span>
                        
                    </div>
                    <div class="stat-card-body">
                        @if($avgLatency !== null)
                            <div class="stat-card-value-row">
                                <span class="stat-card-value">{{ $avgLatency }}</span>
                                <span class="stat-card-unit">ms</span>
                            </div>
                            <span class="stat-card-sub">Average (active devices)</span>
                        @else
                            <div class="stat-card-value-row">
                                <span class="stat-card-value" style="font-size:32px;color:#94a3b8;">—</span>
                            </div>
                            <span class="stat-card-sub">No active devices</span>
                        @endif
                    </div>
                    <div class="stat-card-footer">
                        @if($peakLatency !== null)
                            <span class="stat-footer-text">Peak: {{ $peakLatency }}ms</span>
                            <span class="stat-chip {{ $latencyStatus === 'Stable' ? 'stable-chip' : ($latencyStatus === 'Moderate' ? 'moderate-chip' : 'high-chip') }}">
                                {{ $latencyStatus }}
                            </span>
                        @else
                            <span class="stat-footer-text" style="color:#94a3b8;">All devices offline</span>
                        @endif
                    </div>
                </div>

                <div class="stat-card packet-loss-card">
                    <div class="stat-card-header-row">
                        <span class="stat-card-label">PACKET LOSS</span>
                        
                    </div>
                    <div class="stat-card-body">
                        @if($packetLoss !== null)
                            <div class="stat-card-value-row">
                                <span class="stat-card-value">{{ $packetLoss }}</span>
                                <span class="stat-card-unit">%</span>
                            </div>
                            <span class="stat-card-sub">Global Average (SNMP)</span>
                        @else
                            <div class="stat-card-value-row">
                                <span class="stat-card-value" style="font-size:32px;color:#94a3b8;">—</span>
                            </div>
                            <span class="stat-card-sub">No SNMP data available</span>
                        @endif
                    </div>
                    <div class="stat-card-footer packet-footer">
                        @if($packetLoss !== null)
                        <div class="packet-bar">
                            <div class="packet-bar-fill" style="width:{{ min($packetLoss * 10, 100) }}%;"></div>
                        </div>
                        @else
                        <span style="font-size:12px;color:#94a3b8;">
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3 class="table-card-title">Top Busiest Devices</h3>
                <button class="view-all-btn" id="viewAllBtn" onclick="openAllDevicesPanel()">
                    <span>View All</span>
                </button>
            </div>
            <div class="table-scroll">
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>DEVICE NAME</th>
                            <th>IP ADDRESS</th>
                            <th>BANDWIDTH</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDevices as $device)
                        <tr>
                            <td>
                                <div class="device-name-cell">
                                    
                                    <span>{{ $device->device }}</span>
                                </div>
                            </td>
                            <td><span class="mono-text">{{ $device->ip_address }}</span></td>
                            <td>{{ \App\Models\InterfaceTraffic::formatBytes($device->total_bytes) }}</td>
                            <td>
                                @php
                                    $status    = strtolower($device->status ?? 'unknown');
                                    $dotColor  = $status === 'up' ? '#65F3B6' : ($status === 'down' ? '#ef4444' : '#94a3b8');
                                    $label     = $status === 'up' ? 'Active' : ($status === 'down' ? 'Offline' : 'Unknown');
                                @endphp
                                <div class="status-cell">
                                    <span class="status-dot" style="background:{{ $dotColor }};"></span>
                                    <span>{{ $label }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#94a3b8;padding:24px;">
                                No traffic data available
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3 class="table-card-title">Bandwidth Log</h3>
                <button class="view-all-btn" onclick="openBandwidthLogPanel()">
                    <span>View All</span>
                </button>
            </div>
            <div class="table-scroll">
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>UPLOAD</th>
                            <th>DOWNLOAD</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bandwidthLog->take(7) as $log)
                        <tr>
                            <td><span class="mono-text">{{ \Carbon\Carbon::parse($log->date)->format('d M Y') }}</span></td>
                            <td>{{ \App\Models\InterfaceTraffic::formatBytes($log->total_in) }}</td>
                            <td>{{ \App\Models\InterfaceTraffic::formatBytes($log->total_out) }}</td>
                            <td><strong>{{ \App\Models\InterfaceTraffic::formatBytes($log->total_bytes) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#94a3b8;padding:24px;">
                                No bandwidth log data available
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('partials.footer')

    </div>

    <div class="all-devices-panel" id="allDevicesPanel">
        <div class="panel-header">
            <span class="panel-title">All Devices ({{ $allDevices->count() }})</span>
            <button class="panel-close-btn" onclick="closeAllDevicesPanel()" aria-label="Close panel">
                
                &times;
            </button>
        </div>

        <div class="panel-search-wrap">
            
            <input type="text" class="panel-search-input" id="panelSearchInput" placeholder="Search devices..." oninput="filterPanelDevices()">
        </div>

        <div class="panel-device-list" id="panelDeviceList">
            @forelse($allDevices as $device)
            @php
                $status    = strtolower($device->status ?? 'unknown');
                $dotColor  = $status === 'up' ? '#65F3B6' : ($status === 'down' ? '#ef4444' : '#94a3b8');
                $label     = $status === 'up' ? 'Active' : ($status === 'down' ? 'Offline' : 'Unknown');
                $badgeClass = $status === 'up' ? 'load-badge-active' : ($status === 'down' ? 'load-badge-critical' : '');
            @endphp
            <div class="panel-device-item" data-search="{{ strtolower($device->device) }} {{ strtolower($device->ip_address) }}">
                <div class="panel-device-left">
                    
                    <div class="panel-device-info">
                        <span class="panel-device-name">{{ $device->device }}</span>
                        <span class="panel-device-ip mono-text">{{ $device->ip_address }}</span>
                    </div>
                </div>
                <div class="panel-device-right">
                    <div class="panel-device-status">
                        <span class="status-dot" style="background:{{ $dotColor }};"></span>
                        <span>{{ $label }}</span>
                    </div>
                    <span class="panel-device-bw">{{ \App\Models\InterfaceTraffic::formatBytes($device->total_bytes) }}</span>
                    <span class="load-badge {{ $badgeClass }}">{{ $label }}</span>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                
                <p>No devices found</p>
            </div>
            @endforelse
            <div class="panel-no-results" id="panelNoResults" style="display:none;">
                
                <p>No devices found</p>
            </div>
        </div>
    </div>

    <div class="all-devices-panel" id="bandwidthLogPanel">
        <div class="panel-header">
            <span class="panel-title">Bandwidth Log ({{ $bandwidthLog->count() }} days)</span>
            <button class="panel-close-btn" onclick="closeBandwidthLogPanel()" aria-label="Close panel">
                
                &times;
            </button>
        </div>

        <div class="panel-search-wrap">
            
            <input type="text" class="panel-search-input" id="bwLogSearchInput" placeholder="Search by date " oninput="filterBwLog()">
        </div>

        <div class="panel-device-list" id="bwLogList">
            @forelse($bandwidthLog as $log)
            @php $dateStr = \Carbon\Carbon::parse($log->date)->format('d M Y'); @endphp
            <div class="bwlog-item" data-search="{{ strtolower($dateStr) }}">
                <div class="bwlog-date">
                    
                    <span class="mono-text" style="font-weight:600;color:#2C2F31;">{{ $dateStr }}</span>
                </div>
                <div class="bwlog-stats">
                    <div class="bwlog-stat">
                        <span class="bwlog-stat-label">
                            <span class="breakdown-dot upload-dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10B981;margin-right:4px;"></span>
                            Upload
                        </span>
                        <span class="bwlog-stat-value">{{ \App\Models\InterfaceTraffic::formatBytes($log->total_in) }}</span>
                    </div>
                    <div class="bwlog-stat">
                        <span class="bwlog-stat-label">
                            <span class="breakdown-dot download-dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3B82F6;margin-right:4px;"></span>
                            Download
                        </span>
                        <span class="bwlog-stat-value">{{ \App\Models\InterfaceTraffic::formatBytes($log->total_out) }}</span>
                    </div>
                    <div class="bwlog-stat bwlog-total">
                        <span class="bwlog-stat-label">Total</span>
                        <span class="bwlog-stat-value" style="font-weight:700;color:#2C2F31;">{{ \App\Models\InterfaceTraffic::formatBytes($log->total_bytes) }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                
                <p>No bandwidth log data</p>
            </div>
            @endforelse
            <div class="panel-no-results" id="bwLogNoResults" style="display:none;">
                
                <p>No results found</p>
            </div>
        </div>
    </div>

    <div class="panel-overlay" id="panelOverlay" onclick="closeAllPanels()"></div>

    <script>
        const chartHours  = @json($chartHours);
        const chartValues = @json($chartValues);
    </script>

    <script>
        function closeAllPanels() {
            ['allDevicesPanel', 'bandwidthLogPanel'].forEach(id => {
                document.getElementById(id).classList.remove('open');
            });
            document.getElementById('mainContent').classList.remove('panel-open');
            document.getElementById('panelOverlay').classList.remove('visible');
            document.body.style.overflow = '';
        }

        function openAllDevicesPanel() {
            closeAllPanels();
            document.getElementById('allDevicesPanel').classList.add('open');
            document.getElementById('mainContent').classList.add('panel-open');
            document.getElementById('panelOverlay').classList.add('visible');
            document.body.style.overflow = 'hidden';
            document.getElementById('panelSearchInput').value = '';
            filterPanelDevices();
        }

        function closeAllDevicesPanel() { closeAllPanels(); }

        function filterPanelDevices() {
            const search = document.getElementById('panelSearchInput').value.toLowerCase();
            const items  = document.querySelectorAll('.panel-device-item');
            let found = 0;
            items.forEach(item => {
                const hay = item.dataset.search || '';
                const show = search === '' || hay.includes(search);
                item.style.display = show ? '' : 'none';
                if (show) found++;
            });
            document.getElementById('panelNoResults').style.display = found ? 'none' : 'flex';
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAllPanels();
        });

        function openBandwidthLogPanel() {
            closeAllPanels();
            document.getElementById('bandwidthLogPanel').classList.add('open');
            document.getElementById('mainContent').classList.add('panel-open');
            document.getElementById('panelOverlay').classList.add('visible');
            document.body.style.overflow = 'hidden';
            document.getElementById('bwLogSearchInput').value = '';
            filterBwLog();
        }

        function closeBandwidthLogPanel() { closeAllPanels(); }

        function filterBwLog() {
            const search = document.getElementById('bwLogSearchInput').value.toLowerCase();
            const items  = document.querySelectorAll('.bwlog-item');
            let found = 0;
            items.forEach(item => {
                const hay  = item.dataset.search || '';
                const show = search === '' || hay.includes(search);
                item.style.display = show ? '' : 'none';
                if (show) found++;
            });
            document.getElementById('bwLogNoResults').style.display = found ? 'none' : 'flex';
        }

        (function () {
            const canvas = document.getElementById('bandwidthChart');
            if (!canvas) return;

            const dpr = window.devicePixelRatio || 1;
            const W   = canvas.offsetWidth  || canvas.parentElement.offsetWidth;
            const H   = 192;
            canvas.width  = W * dpr;
            canvas.height = H * dpr;
            canvas.style.height = H + 'px';

            const ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);

            const values = chartValues;
            const labels = chartHours;

            if (!values || values.length === 0) {
                ctx.fillStyle = '#94a3b8';
                ctx.font = '14px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('Belum ada data 24 jam terakhir', W / 2, H / 2);
                return;
            }

            const maxVal = Math.max(...values, 1);
            const padTop = 16, padBot = 24, padL = 4, padR = 4;
            const chartW = W - padL - padR;
            const chartH = H - padTop - padBot;

            const pts = values.map((v, i) => ({
                x: padL + (i / (values.length - 1)) * chartW,
                y: padTop + chartH - (v / maxVal) * chartH,
            }));

            const grad = ctx.createLinearGradient(0, padTop, 0, padTop + chartH);
            grad.addColorStop(0,   'rgba(16,185,129,0.35)');
            grad.addColorStop(1,   'rgba(16,185,129,0)');

            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let i = 1; i < pts.length; i++) {
                ctx.lineTo(pts[i].x, pts[i].y);
            }
            ctx.lineTo(pts[pts.length - 1].x, padTop + chartH);
            ctx.lineTo(pts[0].x, padTop + chartH);
            ctx.closePath();
            ctx.fillStyle = grad;
            ctx.fill();

            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let i = 1; i < pts.length; i++) {
                ctx.lineTo(pts[i].x, pts[i].y);
            }
            ctx.strokeStyle = '#10B981';
            ctx.lineWidth   = 2;
            ctx.stroke();

            ctx.fillStyle   = '#94a3b8';
            ctx.font        = '10px Inter, sans-serif';
            ctx.textAlign   = 'center';
            labels.forEach((label, i) => {
                if (i % 4 === 0) {
                    ctx.fillText(label, pts[i].x, H - 6);
                }
            });
        })();

        window.addEventListener('load', function () {
            const closeBtn = document.querySelector('.panel-close-btn');
            if (closeBtn) {
                const icon     = closeBtn.querySelector('.material-symbols-outlined');
                const fallback = closeBtn.querySelector('.fallback-x');
                if (icon && icon.offsetWidth === 0 && fallback) {
                    icon.style.display     = 'none';
                    fallback.style.display = 'inline';
                }
            }
        });
    </script>
</body>
</html>