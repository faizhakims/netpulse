<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse - Traffic Monitoring</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;700&family=Liberation+Mono&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/traffic.css') }}">
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    {{-- ==================== MAIN CONTENT ==================== --}}
    <div class="main" id="mainContent">

        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Traffic Monitoring</h1>
                <p class="page-subtitle">Real-time analysis of network</p>
            </div>
            <div class="updated-badge">
                <span class="live-dot"></span>
                <span>Updated 3s ago</span>
            </div>
        </div>

        {{-- Hero Row: Bandwidth Card + Stats Column --}}
        <div class="hero-row">
            {{-- Main Hero Card --}}
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
                {{-- Chart --}}
                <div class="chart-container">
                    <svg class="chart-svg" viewBox="0 0 768 192" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#10B981" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                            </linearGradient>
                            <filter id="glow">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <path class="chart-area" d="M0,128 L38,118 L77,132 L115,108 L154,120 L192,95 L230,105 L269,88 L307,98 L346,78 L384,90 L422,72 L461,85 L499,68 L538,80 L576,62 L614,75 L653,58 L691,70 L730,55 L768,65 L768,192 L0,192 Z" />
                        <path class="chart-line-glow" d="M0,128 L38,118 L77,132 L115,108 L154,120 L192,95 L230,105 L269,88 L307,98 L346,78 L384,90 L422,72 L461,85 L499,68 L538,80 L576,62 L614,75 L653,58 L691,70 L730,55 L768,65" filter="url(#glow)" />
                        <path class="chart-line" d="M0,128 L38,118 L77,132 L115,108 L154,120 L192,95 L230,105 L269,88 L307,98 L346,78 L384,90 L422,72 L461,85 L499,68 L538,80 L576,62 L614,75 L653,58 L691,70 L730,55 L768,65" />
                    </svg>
                </div>
            </div>

            {{-- Stats Column --}}
            <div class="stats-column">
                {{-- Latency Card --}}
                <div class="stat-card latency-card">
                    <div class="stat-card-header-row">
                        <span class="stat-card-label">NETWORK LATENCY</span>
                        <span class="material-symbols-outlined stat-card-icon">network_ping</span>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-card-value-row">
                            <span class="stat-card-value">24</span>
                            <span class="stat-card-unit">ms</span>
                        </div>
                        <span class="stat-card-sub">Average</span>
                    </div>
                    <div class="stat-card-footer">
                        <span class="stat-footer-text">Peak: 89ms</span>
                        <span class="stat-chip stable-chip">Stable</span>
                    </div>
                </div>

                {{-- Packet Loss Card --}}
                <div class="stat-card packet-loss-card">
                    <div class="stat-card-header-row">
                        <span class="stat-card-label">PACKET LOSS</span>
                        <span class="material-symbols-outlined stat-card-icon" style="color:#B31B25;">error</span>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-card-value-row">
                            <span class="stat-card-value">0.02</span>
                            <span class="stat-card-unit">%</span>
                        </div>
                        <span class="stat-card-sub">Global Average</span>
                    </div>
                    <div class="stat-card-footer packet-footer">
                        <div class="packet-bar">
                            <div class="packet-bar-fill"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Devices Table --}}
        <div class="table-card">
            <div class="table-card-header">
                <h3 class="table-card-title">Top Busiest Devices</h3>
                <button class="view-all-btn" id="viewAllBtn" onclick="openAllDevicesPanel()">
                    <span>View All</span>
                    <span class="material-symbols-outlined" style="font-size:14px;">arrow_forward_ios</span>
                </button>
            </div>
            <div class="table-scroll">
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>DEVICE NAME</th>
                            <th>IP ADDRESS</th>
                            <th>STATUS</th>
                            <th>BANDWIDTH</th>
                            <th>LOAD</th>
                        </tr>
                    </thead>
                    <tbody>
                                                @foreach($topDevices as $device)
                        <tr>
                            <td>
                                <div class="device-name-cell">
                                    <span class="material-symbols-outlined device-icon">router</span>
                                    <span>{{ $device->device }}</span>
                                </div>
                            </td>
                            <td><span class="mono-text">{{ $device->ip_address }}</span></td>
                            <td>
                                <div class="status-cell">
                                    <span class="status-dot" style="background:#65F3B6;"></span>
                                    <span>Active</span>
                                </div>
                            </td>
                            <td>{{ \App\Models\InterfaceTraffic::formatBytes($device->total_bytes) }}</td>
                            <td>
                                <span class="load-badge load-badge-active">Active</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Page Footer --}}
        <div class="page-footer">
            <span class="footer-copy">&copy; 2026 NetPulse - Network Operations Center</span>
            <div class="footer-status">
                <span class="status-dot-item">API Status: Operational</span>
                <span class="status-dot-item">Database: 4ms Sync</span>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </div>

    </div>{{-- /.main --}}

    {{-- ==================== ALL DEVICES SLIDE PANEL ==================== --}}
    <div class="all-devices-panel" id="allDevicesPanel">
        <div class="panel-header">
            <span class="panel-title">All Devices</span>
            {{-- TOMBOL X SELALU TAMPIL --}}
            <button class="panel-close-btn" onclick="closeAllDevicesPanel()" aria-label="Close panel">
                <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                <span class="fallback-x" style="display:none;">&times;</span>
            </button>
        </div>

        {{-- Panel Search --}}
        <div class="panel-search-wrap">
            <span class="material-symbols-outlined panel-search-icon">search</span>
            <input type="text" class="panel-search-input" id="panelSearchInput" placeholder="Search devices..." oninput="filterPanelDevices()">
        </div>

        {{-- Panel Device List --}}
        <div class="panel-device-list" id="panelDeviceList">
                        @foreach($topDevices as $device)
            <div class="panel-device-item" data-search="{{ strtolower($device->device) }} {{ strtolower($device->ip_address) }}">
                <div class="panel-device-left">
                    <span class="material-symbols-outlined panel-device-icon">router</span>
                    <div class="panel-device-info">
                        <span class="panel-device-name">{{ $device->device }}</span>
                        <span class="panel-device-ip mono-text">{{ $device->ip_address }}</span>
                    </div>
                </div>
                <div class="panel-device-right">
                    <div class="panel-device-status">
                        <span class="status-dot" style="background:#65F3B6;"></span>
                        <span>Active</span>
                    </div>
                    <span class="panel-device-bw">{{ \App\Models\InterfaceTraffic::formatBytes($device->total_bytes) }}</span>
                    
                    <span class="load-badge load-badge-active">Active</span>
                </div>
            </div>
            @endforeach
            <div class="panel-no-results" id="panelNoResults" style="display:none;">
                <span class="material-symbols-outlined" style="font-size:40px;color:#CBD5E1;">search_off</span>
                <p>No devices found</p>
            </div>
        </div>
    </div>

    {{-- Panel Overlay (mobile) --}}
    <div class="panel-overlay" id="panelOverlay" onclick="closeAllDevicesPanel()"></div>

    {{-- JavaScript --}}
    <script>
        function openAllDevicesPanel() {
            document.getElementById('allDevicesPanel').classList.add('open');
            document.getElementById('mainContent').classList.add('panel-open');
            document.getElementById('panelOverlay').classList.add('visible');
            document.body.style.overflow = 'hidden';
            document.getElementById('panelSearchInput').value = '';
            filterPanelDevices();
        }

        function closeAllDevicesPanel() {
            document.getElementById('allDevicesPanel').classList.remove('open');
            document.getElementById('mainContent').classList.remove('panel-open');
            document.getElementById('panelOverlay').classList.remove('visible');
            document.body.style.overflow = '';
        }

        function filterPanelDevices() {
            const search = document.getElementById('panelSearchInput').value.toLowerCase();
            const items = document.querySelectorAll('.panel-device-item');
            let found = 0;
            items.forEach(item => {
                const haystack = item.dataset.search || '';
                if (search === '' || haystack.includes(search)) {
                    item.style.display = '';
                    found++;
                } else {
                    item.style.display = 'none';
                }
            });
            document.getElementById('panelNoResults').style.display = found ? 'none' : 'flex';
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAllDevicesPanel();
        });

        // Simulasi live update
        (function() {
            const badge = document.querySelector('.updated-badge span:last-child');
            let counter = 3;
            if (badge) {
                setInterval(() => {
                    counter = counter >= 15 ? 1 : counter + 1;
                    badge.textContent = 'Updated ' + counter + 's ago';
                }, 3000);
            }
        })();

        // Fallback X jika Material Icons gagal load
        window.addEventListener('load', function() {
            const closeBtn = document.querySelector('.panel-close-btn');
            if (closeBtn) {
                const icon = closeBtn.querySelector('.material-symbols-outlined');
                const fallback = closeBtn.querySelector('.fallback-x');
                // Deteksi sederhana: jika ikon tidak terlihat (offsetWidth 0), tampilkan fallback
                if (icon && icon.offsetWidth === 0 && fallback) {
                    icon.style.display = 'none';
                    fallback.style.display = 'inline';
                }
            }
        });
    </script>
</body>
</html>