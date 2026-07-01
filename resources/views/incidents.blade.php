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
</head>
<body>
    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="main" id="mainContent">

        <div class="page-header">
            <div>
                <h1 class="page-title">Incidents Management</h1>
                <p class="page-subtitle">Real-time overview of network anomalies, device outages, and performance degradation.</p>
            </div>
            <div class="updated-badge">
                <span class="live-dot"></span>
                <span>Live</span>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">DEVICE DOWN</div>
                <div class="stat-value" style="color:#B31B25;">{{ $deviceDown }}</div>
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

            <div class="active-incidents">
                <div class="active-incidents-header">
                    <div>
                        <h2 class="section-title">Active Incidents</h2>
                        <p class="section-subtitle">Auto-resolved when device returns to normal.</p>
                    </div>
                    <div class="toolbar">
                        <div class="search-wrap">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" class="search-input" id="searchInput" placeholder="Search incidents...">
                        </div>
                        <select class="filter-select" id="severityFilter">
                            <option value="all">Severity: All</option>
                            <option value="Critical">Critical</option>
                            <option value="Warning">Warning</option>
                            <option value="Monitoring">Monitoring</option>
                            <option value="Info">Info</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive" id="incidentTableWrap">
                    <table class="incident-table" id="incidentTable">
                        <thead>
                            <tr>
                                <th>INCIDENT ID</th>
                                <th>DEVICE / NODE</th>
                                <th>ISSUE</th>
                                <th>STARTED</th>
                                <th>STATUS</th>
                                <th style="text-align:right;">DURATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeIncidents as $inc)
                            <tr data-status="{{ $inc->status }}"
                                data-search="{{ strtolower('INC-'.str_pad($inc->id,4,'0',STR_PAD_LEFT).' '.($inc->device->name ?? '').' '.$inc->issue) }}">
                                <td><span class="id-text">INC-{{ str_pad($inc->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <div class="device-cell">
                                        <span class="device-name">{{ $inc->device->name ?? '' }}</span>
                                        @if($inc->device->ip_address ?? null)
                                        <span class="device-ip">{{ $inc->device->ip_address }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $inc->issue }}</td>
                                <td class="started-cell">
                                    {{ $inc->started_at ? $inc->started_at->format('d M, H:i') : '-' }}
                                </td>
                                <td>
                                    <span class="badge {{ $inc->badgeClass() }}">{{ $inc->status }}</span>
                                </td>
                                <td style="text-align:right;">{{ $inc->displayDuration() }}</td>
                            </tr>
                            @empty
                            <tr class="empty-row">
                                <td colspan="6" style="text-align:center; padding:40px; color:#94A3B8;">
                                    <span class="material-symbols-outlined" style="font-size:32px; display:block; margin-bottom:8px; color:#CBD5E1;">check_circle</span>
                                    No active incidents — all systems operational.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($activeIncidents->count() > 0)
                <div class="table-info">
                    <span id="visibleCount">{{ $activeIncidents->count() }}</span> incident{{ $activeIncidents->count() !== 1 ? 's' : '' }} active
                    @if($activeIncidents->count() > 6)
                    &nbsp;·&nbsp; scroll to see more
                    @endif
                </div>
                @endif
            </div>

            <div class="resolved-log">
                <div class="resolved-log-header">
                    <h2 class="section-title">Resolved Log</h2>
                </div>
                <div class="resolved-log-list">
                    @forelse($resolvedLog as $log)
                    <div class="log-item">
                        <div class="log-meta">
                            <span class="log-time">{{ $log->resolved_at->format('H:i') }}</span>
                            <span class="log-ago">{{ $log->resolved_at->diffForHumans() }}</span>
                        </div>
                        <div class="log-title">{{ $log->issue }}</div>
                        <div class="log-desc">
                            {{ $log->device->name ?? '' }}
                            @if($log->device->ip_address ?? null) · {{ $log->device->ip_address }}@endif
                            — resolved after {{ $log->displayDuration() }}
                        </div>
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

        <div class="history-panel" id="historyPanel">
            <div class="panel-header">
                <div>
                    <span class="panel-title">Full Incident History</span>
                    <p class="panel-subtitle">All resolved incidents, newest first.</p>
                </div>
                <button class="panel-close-btn" onclick="closeHistory()" aria-label="Close panel">
                    <span class="material-symbols-outlined" style="font-size:20px; color:#64748B;">close</span>
                </button>
            </div>
            <div class="panel-body">
                @forelse($fullHistory as $h)
                <div class="history-item">
                    <div class="history-item-row">
                        <span class="history-id">INC-{{ str_pad($h->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="badge badge-resolved-sm">Resolved</span>
                    </div>
                    <div class="history-device">{{ $h->device->name ?? '' }}@if($h->device->ip_address ?? null) · {{ $h->device->ip_address }}@endif</div>
                    <div class="history-issue">{{ $h->issue }}</div>
                    <div class="history-meta">
                        Started: {{ $h->started_at ? $h->started_at->format('d M Y H:i') : '-' }}
                        &nbsp;→&nbsp;
                        Resolved: {{ $h->resolved_at->format('d M Y H:i') }}
                        &nbsp;·&nbsp;
                        Duration: {{ $h->displayDuration() }}
                    </div>
                </div>
                @empty
                <div class="history-item" style="color:#94A3B8; text-align:center; padding:40px 0;">No history yet.</div>
                @endforelse
            </div>
        </div>
        <div class="history-overlay" id="historyOverlay" onclick="closeHistory()"></div>

        @include('partials.footer')
    </div>

    <script>
        (function() {
            const searchInput    = document.getElementById('searchInput');
            const severityFilter = document.getElementById('severityFilter');
            const rows           = document.querySelectorAll('#incidentTable tbody tr:not(.empty-row)');
            const visibleCount   = document.getElementById('visibleCount');

            function filterRows() {
                const term     = searchInput.value.toLowerCase();
                const severity = severityFilter.value;
                let count = 0;
                rows.forEach(row => {
                    const text   = row.dataset.search || '';
                    const status = row.dataset.status  || '';
                    const show   = (!term || text.includes(term))
                                && (severity === 'all' || status === severity);
                    row.classList.toggle('hidden', !show);
                    if (show) count++;
                });
                if (visibleCount) visibleCount.textContent = count;
            }

            if (searchInput)    searchInput.addEventListener('input', filterRows);
            if (severityFilter) severityFilter.addEventListener('change', filterRows);

            const openBtn  = document.getElementById('openHistoryBtn');
            const panel    = document.getElementById('historyPanel');
            const overlay  = document.getElementById('historyOverlay');

            if (openBtn) openBtn.addEventListener('click', () => {
                panel.classList.add('open');
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            });

            window.closeHistory = function() {
                panel.classList.remove('open');
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            };

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeHistory();
            });

            let autoRefreshTimer = setInterval(() => {
                const panelOpen = panel && panel.classList.contains('open');
                if (!panelOpen) {
                    window.location.reload();
                }
            }, 30000);

            let lastInteraction = Date.now();
            [searchInput, severityFilter].forEach(el => {
                if (el) el.addEventListener('input', () => { lastInteraction = Date.now(); });
            });

        })();
    </script>
</body>
</html>