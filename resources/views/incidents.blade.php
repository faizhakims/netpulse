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
            background: none; border: none; cursor: pointer; padding: 4px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; transition: background 0.15s;
        }
        .panel-close-btn:hover { background: #F1F5F9; }
        tr.hidden { display: none; }
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
            <div class="updated-badge">Updated just now</div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">DEVICE DOWN</div>
                <div class="stat-value">{{ $deviceDown }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">LATENCY SPIKES</div>
                <div class="stat-value" style="color:#FEB64C;">{{ $latencySpikes }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">UNRESOLVED ISSUES</div>
                <div class="stat-value" style="color:#000;">{{ $unresolvedCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">SYSTEM SUCCESS RATE</div>
                <div class="stat-value" style="color:#047857;">{{ $successRate }}%</div>
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
                            @forelse($activeIncidents as $inc)
                            <tr data-status="{{ $inc->status }}"
                                data-search="{{ strtolower('INC-'.str_pad($inc->id,4,'0',STR_PAD_LEFT).' '.$inc->device.' '.$inc->issue) }}">
                                <td><span class="id-text">INC-{{ str_pad($inc->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td>{{ $inc->device }}</td>
                                <td>{{ $inc->issue }}</td>
                                <td>
                                    <span class="badge {{ $inc->badgeClass() }}">{{ $inc->status }}</span>
                                </td>
                                <td style="text-align:right;">{{ $inc->displayDuration() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center; padding:32px; color:#94A3B8;">
                                    No active incidents — all systems operational.
                                </td>
                            </tr>
                            @endforelse
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
                    @forelse($resolvedLog as $log)
                    <div class="log-item">
                        <div class="log-meta">
                            <span class="log-time">{{ $log->resolved_at->format('h:i A') }}</span>
                            <span class="log-ago">{{ $log->resolved_at->diffForHumans() }}</span>
                        </div>
                        <div class="log-title">{{ $log->issue }}</div>
                        <div class="log-desc">{{ $log->device }} — resolved after {{ $log->displayDuration() }}</div>
                    </div>
                    @empty
                    <div class="log-item">
                        <div class="log-desc" style="color:#94A3B8;">No resolved incidents yet.</div>
                    </div>
                    @endforelse
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
                @forelse($fullHistory as $h)
                <div class="history-item">
                    <strong>INC-{{ str_pad($h->id, 4, '0', STR_PAD_LEFT) }}</strong>
                    – {{ $h->device }} – {{ $h->issue }}
                    – {{ $h->displayDuration() }}
                    – <span style="color:#047857;">Resolved</span>
                    <span style="color:#94A3B8; font-size:12px; margin-left:8px;">
                        {{ $h->resolved_at->format('d M Y H:i') }}
                    </span>
                </div>
                @empty
                <div class="history-item" style="color:#94A3B8;">No history yet.</div>
                @endforelse
            </div>
        </div>

        <div class="page-footer">
            <span class="footer-copy">&copy; 2026 NetPulse – Network Operations Center</span>
            <div class="footer-status">
                <span class="status-dot">API Status: Operational</span>
                <span class="status-dot">Database: Live</span>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const searchInput    = document.getElementById('searchInput');
            const severityFilter = document.getElementById('severityFilter');
            const rows           = document.querySelectorAll('#incidentTable tbody tr');

            function filterRows() {
                const term     = searchInput.value.toLowerCase();
                const severity = severityFilter.value;
                rows.forEach(row => {
                    const text   = row.dataset.search || '';
                    const status = row.dataset.status  || '';
                    const show   = (!term || text.includes(term))
                                && (severity === 'all' || status === severity);
                    row.classList.toggle('hidden', !show);
                });
            }
            searchInput.addEventListener('input', filterRows);
            severityFilter.addEventListener('change', filterRows);

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
