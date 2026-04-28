<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse – Incidents Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/incidents.css') }}">
    <style>
        .panel-close-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .panel-close-btn:hover { background: #F1F5F9; }
    </style>
</head>
<body>
    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="main" id="mainContent">
        <div class="page-header">
            <div>
                <h1 class="page-title">Incidents Management</h1>
                <p class="page-subtitle">Real‑time overview of network anomalies, device outages, and performance degradation.</p>
            </div>
            <div class="updated-badge">Updated 3s ago</div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">DEVICE DOWN</div>
                <div class="stat-value">248</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">LATENCY SPIKES</div>
                <div class="stat-value" style="color:#FEB64C;">47</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">UNRESOLVED ISSUES</div>
                <div class="stat-value" style="color:#000;">23</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">SYSTEM SUCCESS RATE</div>
                <div class="stat-value" style="color:#047857;">99.8%</div>
            </div>
        </div>

        <div class="main-content-row">
            {{-- Active Incidents --}}
            <div class="active-incidents">
                <div class="active-incidents-header">
                    <h2 class="section-title">Active Incidents</h2>
                    <div class="toolbar">
                        <div class="search-wrap">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" class="search-input" id="searchInput" placeholder="Search incidents...">
                        </div>
                        <select class="filter-select" id="severityFilter">
                            <option value="all">Severity: All</option>
                            <option value="Critical">Critical</option>
                            <option value="Warning">Warning</option>
                            <option value="Info">Info</option>
                            <option value="Monitoring">Monitoring</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="incident-table" id="incidentTable">
                        <thead>
                            <tr>
                                <th>INCIDENT ID</th>
                                <th>DEVICE / NODE</th>
                                <th>ISSUE</th>
                                <th>STATUS</th>
                                <th>DURATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $incidents = [
                                ['id'=>'INC-0042','device'=>'Core Router Alpha-01','issue'=>'Connection lost – no traffic','status'=>'Critical','duration'=>'1h 12m'],
                                ['id'=>'INC-0041','device'=>'Switch 12F – Floor 3','issue'=>'Packet loss > 15%','status'=>'Warning','duration'=>'45m'],
                                ['id'=>'INC-0040','device'=>'AP Lobby-02','issue'=>'Latency spike – 120ms','status'=>'Monitoring','duration'=>'2m 14s'],
                                ['id'=>'INC-0039','device'=>'Firewall Perimeter','issue'=>'Interface flapping','status'=>'Info','duration'=>'18s'],
                                ['id'=>'INC-0038','device'=>'Switch-Core-01','issue'=>'Spanning Tree Topology Change','status'=>'Warning','duration'=>'3m 05s'],
                            ];
                            @endphp
                            @foreach($incidents as $inc)
                            <tr data-status="{{ $inc['status'] }}" data-search="{{ strtolower($inc['id'].' '.$inc['device'].' '.$inc['issue']) }}">
                                <td><span class="id-text">{{ $inc['id'] }}</span></td>
                                <td>{{ $inc['device'] }}</td>
                                <td>{{ $inc['issue'] }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($inc['status']) {
                                            'Critical' => 'badge-critical',
                                            'Warning'  => 'badge-high',
                                            'Monitoring' => 'badge-normal',
                                            default    => 'badge-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $inc['status'] }}</span>
                                </td>
                                <td style="text-align:right;">{{ $inc['duration'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Resolved Log Sidebar --}}
            <div class="resolved-log">
                <div class="resolved-log-header">
                    <h2 class="section-title">Resolved Log</h2>
                </div>
                <div class="resolved-log-list">
                    <div class="log-item">
                        <div class="log-meta">
                            <span class="log-time">10:45 AM</span>
                            <span class="log-ago">5m ago</span>
                        </div>
                        <div class="log-title">Database Sync Restored</div>
                        <div class="log-desc">Resolved by Auto-healing Protocol X.</div>
                    </div>
                    <div class="log-item">
                        <div class="log-meta">
                            <span class="log-time">09:12 AM</span>
                            <span class="log-ago">1h ago</span>
                        </div>
                        <div class="log-title">Bandwidth Limit Released</div>
                        <div class="log-desc">Manual intervention by Operator J.Smith.</div>
                    </div>
                    <div class="log-item">
                        <div class="log-meta">
                            <span class="log-time">08:30 AM</span>
                            <span class="log-ago">2h ago</span>
                        </div>
                        <div class="log-title">Node Reboot Successful</div>
                        <div class="log-desc">Scheduled maintenance task completed.</div>
                    </div>
                </div>
                <div class="view-full-wrapper">
                    <hr class="resolved-separator">
                    <button class="btn-view-full" id="openHistoryBtn">View Full History</button>
                </div>
            </div>
        </div>

        {{-- Full History Panel --}}
        <div class="history-panel" id="historyPanel">
            <div class="panel-header">
                <span class="panel-title">Full Incident History</span>
                <button class="panel-close-btn" onclick="closeHistory()" aria-label="Close panel">
                    <span class="material-symbols-outlined" style="font-size:20px; color:#64748B;">close</span>
                </button>
            </div>
            <div class="panel-body">
                <div class="history-item"><strong>INC-0050</strong> – Router AB1 – Packet loss – 2m – Resolved</div>
                <div class="history-item"><strong>INC-0049</strong> – Switch Core – High CPU – 5m – Resolved</div>
                <div class="history-item"><strong>INC-0048</strong> – AP Office – Latency spike – 1m – Resolved</div>
                <div class="history-item"><strong>INC-0047</strong> – Firewall – Interface down – 30s – Resolved</div>
                <div class="history-item"><strong>INC-0046</strong> – Router AB1 – Connection lost – 1h – Resolved</div>
                <div class="history-item"><strong>INC-0045</strong> – Switch 12F – Packet loss – 45m – Resolved</div>
                <div class="history-item"><strong>INC-0044</strong> – AP Lobby – Latency – 2m – Resolved</div>
                <div class="history-item"><strong>INC-0043</strong> – Firewall – Flapping – 18s – Resolved</div>
                <div class="history-item"><strong>INC-0042</strong> – Core Router – Critical – 1h12m – Resolved</div>
                <div class="history-item"><strong>INC-0041</strong> – Switch 12F – High – 45m – Resolved</div>
                <div class="history-item"><strong>INC-0040</strong> – AP Lobby – Monitoring – 2m – Resolved</div>
                <div class="history-item"><strong>INC-0039</strong> – Firewall – Info – 18s – Resolved</div>
            </div>
        </div>

        <div class="page-footer">
            <span class="footer-copy">&copy; 2026 NetPulse – Network Operations Center</span>
            <div class="footer-status">
                <span class="status-dot">API Status: Operational</span>
                <span class="status-dot">Database: 4ms Sync</span>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // Filter
            const searchInput = document.getElementById('searchInput');
            const severityFilter = document.getElementById('severityFilter');
            const rows = document.querySelectorAll('#incidentTable tbody tr');

            function filterRows() {
                const term = searchInput.value.toLowerCase();
                const severity = severityFilter.value;
                rows.forEach(row => {
                    const text = row.dataset.search || '';
                    const status = row.dataset.status || '';
                    const show = (!term || text.includes(term)) && (severity === 'all' || status === severity);
                    row.classList.toggle('hidden', !show);
                });
            }
            searchInput.addEventListener('input', filterRows);
            severityFilter.addEventListener('change', filterRows);

            // History panel
            document.getElementById('openHistoryBtn').addEventListener('click', () => {
                document.getElementById('historyPanel').classList.add('open');
            });
            window.closeHistory = function() {
                document.getElementById('historyPanel').classList.remove('open');
            };
        })();
    </script>
</body>
</html>