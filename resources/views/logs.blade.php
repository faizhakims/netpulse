<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse - Logs & Activity</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/logs.css') }}">
    <style>
        .panel-close-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .panel-close-btn:hover { background: #F1F5F9; }
        @media (max-width: 768px) {
            .panel-close-btn { display: flex; }
        }
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="main" id="mainContent">

        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Logs & Activity</h1>
                <p class="page-subtitle">Track all system, network, and user events in real time</p>
            </div>
            <div class="updated-badge">Updated 3s ago</div>
        </div>

        {{-- Stats --}}
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Logs Today</div>
                <div class="stat-value">{{ $totalLogs }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Critical Events</div>
                <div class="stat-value critical">{{ $criticalLogs }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Warnings</div>
                <div class="stat-value warning">{{ $warningLogs }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">User Actions</div>
                <div class="stat-value success">{{ $infoLogs }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="table-wrapper">

            <div class="toolbar">
                <div class="search-wrap">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search logs...">
                </div>
                <select class="filter-select" id="typeFilter">
                    <option value="all">Type: All</option>
                    <option value="System">System</option>
                    <option value="Network">Network</option>
                    <option value="User">User</option>
                </select>
                <select class="filter-select" id="severityFilter">
                    <option value="all">Severity: All</option>
                    <option value="Critical">Critical</option>
                    <option value="Warning">Warning</option>
                    <option value="Info">Info</option>
                    <option value="Success">Success</option>
                    <option value="Debug">Debug</option>
                </select>
                <input type="date" class="date-input" id="dateFilter">
                <div class="toolbar-right">
                    <button class="export-btn">Export .CSV</button>
                    <button class="export-btn">Export .PDF</button>
                </div>
            </div>

            <div class="table-scroll">
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Level</th>
                            <th>Device</th>
                            <th>Event</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        

                        @foreach($logs as $log)
                        <tr data-id="{{ $log['id'] }}"
                            data-level="{{ $log['level'] }}"
                            data-type="{{ $log['type'] }}"
                            data-date="2026-04-22"
                            onclick="openDetail({{ json_encode($log) }})"
                            id="row-{{ $log['id'] }}">
                            <td class="date-cell">{{ $log['date'] }}</td>
                            <td class="time-cell">{{ $log['time'] }}</td>
                            <td>
                                @php
                                    $badgeClass = match($log['level']) {
                                        'Critical' => 'badge-critical',
                                        'Warning'  => 'badge-warning',
                                        'Info'     => 'badge-info',
                                        'Success'  => 'badge-success',
                                        default    => 'badge-debug',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $log['level'] }}</span>
                            </td>
                            <td>{{ $log['device'] }}</td>
                            <td>{{ $log['event'] }}</td>
                            <td style="color:#94A3B8; font-size:12px;">{{ $log['source'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span class="showing-text" id="showingText">Showing 1 to 15 of 248 entries</span>
                <div class="pagination">
                    <button class="page-btn">&#8249;</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <span class="page-dots">…</span>
                    <button class="page-btn">50</button>
                    <button class="page-btn">&#8250;</button>
                </div>
            </div>
        </div>

        {{-- Page Footer --}}
        <div class="page-footer">
            <span class="footer-copy">© 2026 NetPulse - Network Operations Center</span>
            <div class="footer-status">
                <span class="status-dot">API Status: Operational</span>
                <span class="status-dot">Database: 4ms Sync</span>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </div>

    </div>{{-- /.main --}}

    {{-- Detail Panel (tanpa tombol X) --}}
    <div class="detail-panel" id="detailPanel">

        <div class="panel-header">
            <span class="panel-title">Log Detail</span>
            <button class="panel-close-btn" onclick="closePanel()" aria-label="Close panel">
                <span class="material-symbols-outlined" style="font-size:20px; color:#64748B;">close</span>
            </button>
        </div>

        <div class="panel-body">
            <div class="log-id-chip" id="panelId">LOG-0000</div>
            <div class="detail-section">
                <div class="detail-section-title">Overview</div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-item-label">Date</div>
                        <div class="detail-item-value" id="panelDate">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Time</div>
                        <div class="detail-item-value mono" id="panelTime">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Level</div>
                        <div class="detail-item-value" id="panelLevel">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Device</div>
                        <div class="detail-item-value" id="panelDevice">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">IP Address</div>
                        <div class="detail-item-value mono" id="panelIp">—</div>
                    </div>
                    <div class="detail-item full-width">
                        <div class="detail-item-label">Event</div>
                        <div class="detail-item-value" id="panelEvent">—</div>
                    </div>
                    <div class="detail-item full-width">
                        <div class="detail-item-label">Source</div>
                        <div class="detail-item-value" id="panelSource">—</div>
                    </div>
                </div>
            </div>
            <hr class="panel-divider">
            <div class="detail-section">
                <div class="detail-section-title">Description</div>
                <div class="description-box" id="panelDesc">—</div>
            </div>
        </div>

        <div class="panel-actions">
            <button class="action-btn" onclick="viewDevice()">
                <span class="material-symbols-outlined" style="font-size:15px;">router</span>
                View Device
            </button>
            <button class="action-btn" onclick="closePanel()">
                <span class="material-symbols-outlined" style="font-size:15px;">close</span>
                Close
            </button>
        </div>
    </div>

    <script>
        let activeRow = null;

        function getBadgeHtml(level) {
            const map = {
                'Critical': 'badge-critical',
                'Warning':  'badge-warning',
                'Info':     'badge-info',
                'Success':  'badge-success',
                'Debug':    'badge-debug',
            };
            return `<span class="badge ${map[level] || 'badge-debug'}">${level}</span>`;
        }

        function openDetail(log) {
            if (activeRow) activeRow.classList.remove('active');
            activeRow = document.getElementById('row-' + log.id);
            if (activeRow) activeRow.classList.add('active');

            document.getElementById('panelId').textContent     = log.id;
            document.getElementById('panelDate').textContent   = log.date || '—';
            document.getElementById('panelTime').textContent   = log.time;
            document.getElementById('panelLevel').innerHTML    = getBadgeHtml(log.level);
            document.getElementById('panelDevice').textContent = log.device;
            document.getElementById('panelIp').textContent     = log.ip;
            document.getElementById('panelEvent').textContent  = log.event;
            document.getElementById('panelSource').textContent = log.source;
            document.getElementById('panelDesc').textContent   = log.desc;

            document.getElementById('detailPanel').classList.add('open');
            document.getElementById('mainContent').classList.add('panel-open');
        }

        function closePanel() {
            document.getElementById('detailPanel').classList.remove('open');
            document.getElementById('mainContent').classList.remove('panel-open');
            if (activeRow) { activeRow.classList.remove('active'); activeRow = null; }
        }

        function viewDevice() {
            window.location.href = '/devices';
        }

        // Filter functionality
        (function() {
            const searchInput = document.getElementById('searchInput');
            const typeFilter = document.getElementById('typeFilter');
            const severityFilter = document.getElementById('severityFilter');
            const dateFilter = document.getElementById('dateFilter');
            const rows = document.querySelectorAll('#tableBody tr');
            const showingText = document.getElementById('showingText');

            function filterRows() {
                const searchTerm = searchInput.value.toLowerCase();
                const type = typeFilter.value;
                const severity = severityFilter.value;
                const date = dateFilter.value;

                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const rowType = row.dataset.type;
                    const rowLevel = row.dataset.level;
                    const rowDate = row.dataset.date || '2026-04-22'; // fallback

                    let show = true;

                    if (searchTerm && !text.includes(searchTerm)) show = false;
                    if (type !== 'all' && rowType !== type) show = false;
                    if (severity !== 'all' && rowLevel !== severity) show = false;
                    if (date && rowDate !== date) show = false;

                    if (show) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });

                showingText.textContent = `Showing 1 to ${visibleCount} of ${rows.length} entries`;
            }

            searchInput.addEventListener('input', filterRows);
            typeFilter.addEventListener('change', filterRows);
            severityFilter.addEventListener('change', filterRows);
            dateFilter.addEventListener('change', filterRows);
        })();
    </script>
</body>
</html>