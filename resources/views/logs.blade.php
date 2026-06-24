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
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="main" id="mainContent">

<div class="page-header">
            <div>
                <h1 class="page-title">Logs & Activity</h1>
                <p class="page-subtitle">Track all system, network, and user events in real time</p>
            </div>
        </div>

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
                
                <div class="date-range">
                    <label for="dateFrom" class="date-label">From</label>
                    <input type="date" class="date-input" id="dateFrom" placeholder="Start date">
                    <label for="dateTo" class="date-label">To</label>
                    <input type="date" class="date-input" id="dateTo" placeholder="End date">
                </div>
                <div class="toolbar-right">
                    <button class="export-btn" onclick="exportCSV()">
                        <span class="material-symbols-outlined" style="font-size:14px;vertical-align:-2px;">download</span>
                        Export CSV
                    </button>
                    <button class="export-btn export-btn-pdf" onclick="exportPDF()">
                        <span class="material-symbols-outlined" style="font-size:14px;vertical-align:-2px;">picture_as_pdf</span>
                        Export PDF
                    </button>
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
                            data-date="{{ $log['date_filter'] ?? '' }}"
                            data-log='@json($log)'
                            onclick="openDetail(this.dataset.log)"
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
                <span class="showing-text" id="showingText">—</span>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>

@include('partials.footer')

    </div>

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
                    <div class="detail-item">
                        <div class="detail-item-label">Type</div>
                        <div class="detail-item-value" id="panelType">—</div>
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
        const map = { Critical:'badge-critical', Warning:'badge-warning',
                      Info:'badge-info', Success:'badge-success', Debug:'badge-debug' };
        const span = document.createElement('span');
        span.className = 'badge ' + (map[level] || 'badge-debug');
        span.textContent = level;
        return span;
    }

    function openDetail(logData) {
        const log = typeof logData === 'string' ? JSON.parse(logData) : logData;

        if (activeRow) activeRow.classList.remove('active');
        activeRow = document.getElementById('row-' + log.id);
        if (activeRow) activeRow.classList.add('active');

        document.getElementById('panelId').textContent     = log.id      || '—';
        document.getElementById('panelDate').textContent   = log.date    || '—';
        document.getElementById('panelTime').textContent   = log.time    || '—';
        document.getElementById('panelLevel').replaceChildren(getBadgeHtml(log.level));

        document.getElementById('panelDevice').textContent = log.device  || '—';
        document.getElementById('panelIp').textContent     = log.ip      || '—';
        document.getElementById('panelType').textContent   = log.type    || '—';
        document.getElementById('panelEvent').textContent  = log.event   || '—';
        document.getElementById('panelSource').textContent = log.source  || '—';
        document.getElementById('panelDesc').textContent   = log.desc    || '—';

        document.getElementById('detailPanel').classList.add('open');
        document.getElementById('mainContent').classList.add('panel-open');
    }

    function closePanel() {
        document.getElementById('detailPanel').classList.remove('open');
        document.getElementById('mainContent').classList.remove('panel-open');
        if (activeRow) { activeRow.classList.remove('active'); activeRow = null; }
    }

    function viewDevice() { window.location.href = '/device'; }

    const ROWS_PER_PAGE = 15;
    let currentPage  = 1;
    let filteredRows = [];

    function getAllRows() {
        return Array.from(document.querySelectorAll('#tableBody tr'));
    }

    function applyFilters() {
        const search   = document.getElementById('searchInput').value.toLowerCase();
        const type     = document.getElementById('typeFilter').value;
        const severity = document.getElementById('severityFilter').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo   = document.getElementById('dateTo').value;

        filteredRows = getAllRows().filter(row => {
            const text = row.textContent.toLowerCase();
            if (search   && !text.includes(search)) return false;
            if (type     !== 'all' && row.dataset.type  !== type)   return false;
            if (severity !== 'all' && row.dataset.level !== severity) return false;

            const rowDate = row.dataset.date;   // YYYY-MM-DD from date_filter
            if (dateFrom && rowDate < dateFrom) return false;
            if (dateTo   && rowDate > dateTo)   return false;

            return true;
        });

        currentPage = 1;
        renderPage();
    }

    function renderPage() {
        getAllRows().forEach(r => r.classList.add('hidden'));

        const start    = (currentPage - 1) * ROWS_PER_PAGE;
        const end      = start + ROWS_PER_PAGE;
        filteredRows.slice(start, end).forEach(r => r.classList.remove('hidden'));

        const total    = filteredRows.length;
        const showFrom = total === 0 ? 0 : start + 1;
        const showTo   = Math.min(end, total);
        document.getElementById('showingText').textContent =
            `Showing ${showFrom}–${showTo} of ${total} entries`;

        renderPagination(total);
    }

    function renderPagination(total) {
        const totalPages = Math.ceil(total / ROWS_PER_PAGE);
        const pg = document.getElementById('pagination');
        pg.innerHTML = '';

        const prev = mkBtn('&#8249;', currentPage === 1);
        prev.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPage(); } });
        pg.appendChild(prev);

        pageNumbers(currentPage, totalPages).forEach(p => {
            if (p === '...') {
                const d = document.createElement('span');
                d.className = 'page-dots'; d.textContent = '…';
                pg.appendChild(d);
            } else {
                const btn = mkBtn(p, false, p === currentPage);
                btn.addEventListener('click', () => { currentPage = p; renderPage(); });
                pg.appendChild(btn);
            }
        });

        const next = mkBtn('&#8250;', currentPage >= totalPages || totalPages === 0);
        next.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; renderPage(); } });
        pg.appendChild(next);
    }

    function pageNumbers(cur, total) {
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        const pages = [1];
        if (cur > 3) pages.push('...');
        const s = Math.max(2, cur - 1), e = Math.min(total - 1, cur + 1);
        for (let i = s; i <= e; i++) pages.push(i);
        if (cur < total - 2) pages.push('...');
        pages.push(total);
        return pages;
    }

    function mkBtn(label, disabled = false, active = false) {
        const b = document.createElement('button');
        b.className = 'page-btn' + (active ? ' active' : '');
        b.innerHTML = label;
        b.disabled  = disabled;
        if (disabled) b.style.opacity = '0.4';
        return b;
    }

    function exportCSV() {
        const headers = ['ID','Date','Time','Level','Type','Device','IP','Event','Source','Description'];
        const rows = filteredRows.map(row => {
            const log = JSON.parse(row.dataset.log);
            return [
                log.id, log.date, log.time, log.level, log.type,
                log.device, log.ip||'', log.event, log.source, log.desc||''
            ].map(v => `"${String(v||'').replace(/"/g,'""')}"`).join(',');
        });
        const csv  = [headers.join(','), ...rows].join('\n');
        const blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8;'});
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `netpulse-logs-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function exportPDF() {
        const now       = new Date();
        const dateStr   = now.toLocaleDateString('en-GB', {day:'2-digit',month:'long',year:'numeric'});
        const timeStr   = now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
        const refCode   = `NPL-${now.getFullYear()}${String(now.getMonth()+1).padStart(2,'0')}${String(now.getDate()).padStart(2,'0')}-${String(now.getHours()).padStart(2,'0')}${String(now.getMinutes()).padStart(2,'0')}`;
        const total     = filteredRows.length;
        const crit      = filteredRows.filter(r => r.dataset.level === 'Critical').length;
        const warn      = filteredRows.filter(r => r.dataset.level === 'Warning').length;
        const info      = filteredRows.filter(r => ['Info','Success'].includes(r.dataset.level)).length;

        const lvlColor  = {Critical:'#B31B25',Warning:'#D97706',Info:'#095BAC',Success:'#047857',Debug:'#64748B'};

        const tableRows = filteredRows.map((row, i) => {
            const log = JSON.parse(row.dataset.log);
            const lc  = lvlColor[log.level] || '#64748B';
            const bg  = i % 2 === 0 ? '#ffffff' : '#F8FAFC';
            return `<tr style="background:${bg}">
                <td>${log.date||''}</td>
                <td style="font-family:monospace;font-size:10.5px;">${log.time||''}</td>
                <td><span style="background:${lc};color:#fff;padding:2px 8px;border-radius:20px;font-size:9.5px;font-weight:700;">${log.level||''}</span></td>
                <td>${log.device||''}</td>
                <td style="max-width:280px;">${log.event||''}</td>
                <td style="color:#64748B;">${log.source||''}</td>
            </tr>`;
        }).join('');

        const win = window.open('','_blank','width=1200,height=900');
        win.document.write(`<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>NetPulse — Log Report</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.wrap{padding:40px 48px;max-width:1200px;margin:0 auto}
.print-bar{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 18px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#475569}
.print-btn{background:#047857;color:#fff;border:none;border-radius:6px;padding:7px 18px;font-size:12px;font-weight:600;cursor:pointer}
.header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:20px;border-bottom:3px solid #047857;margin-bottom:28px}
.brand{display:flex;align-items:center;gap:14px}
.brand-logo{display:block;height:28px;width:auto}
.brand-sub{font-size:10px;font-weight:600;letter-spacing:0.09em;text-transform:uppercase;color:#64748B;margin-top:5px}
.report-meta{text-align:right}
.report-title{font-size:17px;font-weight:700;color:#0f172a;letter-spacing:-0.3px}
.report-ref{font-size:11px;color:#94A3B8;margin-top:3px;font-family:monospace}
.report-date{font-size:11px;color:#64748B;margin-top:2px}
.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px}
.card{border:1px solid #E2E8F0;border-radius:10px;padding:14px 18px}
.card-label{font-size:9px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:#94A3B8;margin-bottom:8px}
.card-val{font-size:26px;font-weight:700;color:#0f172a;letter-spacing:-1px}
.card-val.r{color:#B31B25}.card-val.a{color:#D97706}.card-val.g{color:#047857}
.section-hd{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.section-lbl{font-size:10px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:#64748B}
.section-pill{background:#F1F5F9;border-radius:20px;padding:2px 10px;font-size:10px;font-weight:600;color:#475569}
table{width:100%;border-collapse:collapse;font-size:11.5px}
thead tr{background:#F1F5F9}
thead th{padding:10px 12px;text-align:left;font-weight:700;font-size:9.5px;letter-spacing:0.07em;text-transform:uppercase;color:#64748B;border-bottom:1px solid #E2E8F0}
tbody tr{border-bottom:1px solid #F1F5F9}
tbody tr:last-child{border-bottom:none}
tbody td{padding:8px 12px;color:#334155;vertical-align:middle}
.footer{margin-top:32px;padding-top:16px;border-top:1px solid #E2E8F0;display:flex;justify-content:space-between;font-size:10px;color:#94A3B8}
.footer strong{color:#64748B}
@media print{.print-bar{display:none!important}.wrap{padding:0}@page{margin:1.2cm;size:A4 landscape}thead{display:table-header-group}tbody tr{page-break-inside:avoid}}
</style>
</head><body>
<div class="wrap">
<div class="print-bar">
  <span>Preview — <strong>NetPulse Log Report</strong> &mdash; ${dateStr} at ${timeStr}</span>
  <button class="print-btn" onclick="window.print()">🖨&nbsp;Print / Save PDF</button>
</div>
<div class="header">
  <div class="brand">
    <div>
      <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzQyIiBoZWlnaHQ9IjQ0IiB2aWV3Qm94PSIwIDAgMzQyIDQ0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cGF0aCBkPSJNMTMzLjUzNiAwLjQ4NDk4NUMxNDAuNDE0IDAuNTk5MzI3IDE0Ny4wOTkgMC4zODg3MTkgMTU0LjMxIDAuNjc2MDQ5QzE2OC41NjIgMS4yNDMzMyAxNzQuNDE2IDE3LjI2MTUgMTYzLjIxMyAyNi4xMDYxQzE2MC45NDIgMjcuODk3OSAxNTcuOTI2IDI4LjUwNDcgMTU1LjA1NCAyOS4xNDYxQzE0OS45NjcgMjkuMjc5MiAxNDQuNTc1IDI5LjE5ODggMTM5LjQ2MiAyOS4xOTc3QzEzOS42MDYgMzMuNTY0MSAxMzkuNjA3IDM3LjkzMzggMTM5LjQ2NiA0Mi4zMDA2QzEzNy41NCA0Mi4xNzc4IDEzNS41MjYgNDIuMjQ0OSAxMzMuNTkgNDIuMjcxOEwxMzMuNTM2IDAuNDg0OTg1Wk0xNTkuNTI2IDIxLjkzNTlDMTYyLjAyMSAxOS4yMjI3IDE2Mi44MTEgMTguMTY4OSAxNjIuNzE4IDE0LjQwNjNDMTYyLjQ0NSAzLjIxNTE4IDE0Ny4xODMgNS44NzUyOCAxMzkuNDkxIDUuNjY1MDRDMTM5LjU2MSAxMS44MjQgMTM5LjU5NCAxNy45ODM0IDEzOS41ODkgMjQuMTQyN0MxNDYuMDQ1IDI0LjE0NDkgMTU0LjI4OSAyNS4xMzQ1IDE1OS41MjYgMjEuOTM1OVoiIGZpbGw9IiMwNjRFM0IiLz4KPHBhdGggZD0iTTMxLjc2NiAwLjQ4MzM5OEwzOC4wNTI1IDAuNDk3MDQxQzM3LjgxNTUgMTMuMzE5MiAzOC41Njg5IDI5LjQ4OSAzNy45NTQ3IDQxLjg3NDFMMzcuNDIwOSA0Mi4yNTE4TDMyLjgwNDQgNDIuMTUxNUMyNS44ODYgMzQuNTA0NiAxOS4zODczIDI2LjIzOTEgMTIuNTQ4NiAxOC40OTkzQzEwLjM3MjEgMTYuMDM1OCA4LjA3MzQ4IDEzLjMyOTUgNi4xMTk2MSAxMC43MDI2TDYuMTUzOTUgNDIuMjI1NkM0LjAzNTgyIDQyLjE1MTggMi4xMDg1MyA0Mi4yNDU5IDAgNDIuMzM0NEMwLjIzMTQ1MSAyOC41NjU4IDAuMDI2OTQ0MiAxNC4yOTU1IDAuMDEwNzAyIDAuNDg5Mjk3TDUuMDE5OTQgMC41OTEwOThDNy4xMDQ4NSAyLjQ2NTIgMTAuNzU1MyA3LjE5NDU0IDEyLjc2MzggOS40NDE5MkMxOS4xNDQgMTYuNTgwNSAyNS4zNTg5IDI0Ljc5MDMgMzEuODQxNyAzMS43ODE3QzMxLjY2NTYgMjEuNDYxNSAzMS44NjM1IDEwLjg2ODIgMzEuNzY2IDAuNDgzMzk4WiIgZmlsbD0iIzA2NEUzQiIvPgo8cGF0aCBkPSJNMzA5LjI4NiAwLjUxMDAxTDM0MC42MTggMC40OTI2NzZMMzQwLjYwMyA1Ljc1NDYzTDMxNS43MTYgNS42ODU2NUwzMTUuNzM4IDE4LjQ0NTVMMzM3LjU4NCAxOC4zOTY0TDMzNy41ODggMjMuNjYzMkwzMTYuNDk5IDIzLjY2MUwzMTUuODcxIDIzLjg5MDRDMzE1LjU3OSAyNS4zOTQyIDMxNS43MTYgMzQuODU1IDMxNS43MiAzNi45Mjk4SDM0MS4yOUwzNDEuMjk3IDQyLjIwMDJDMzMwLjkwNiA0Mi4xODA3IDMxOS43MTcgNDEuOTc2MyAzMDkuMzk2IDQyLjI3ODhDMzA5LjQzIDI4LjM1NiAzMDkuMzkzIDE0LjQzMjggMzA5LjI4NiAwLjUxMDAxWiIgZmlsbD0iIzA2NEUzQiIvPgo8cGF0aCBkPSJNODMuMDI2IDAuNDgyNjY2TDgyLjk5NSA1LjcyOTg2Qzc0Ljk1MjEgNS44NDA4OCA2Ni41MDI5IDUuNzEyNTMgNTguNDMzMSA1LjcwMDczQzU4LjQyMDkgOS45NTAxOSA1OC40MzkgMTQuMiA1OC40ODg0IDE4LjQ0OTlMNzkuOTcyOCAxOC4zNzM5TDc5Ljk0ODQgMjMuNjcxMkw1OS45MjM3IDIzLjY2NjFDNTkuMjcxOCAyMy42NDIxIDU5LjI4MTQgMjMuNjk2NyA1OC42NzcxIDIzLjk3NDFDNTguMDkzMSAyNi4xMzQ3IDU4LjQwNjkgMzQuMTg4NSA1OC40Njg5IDM2Ljk0MzdMODMuNjY3OSAzNi45MDQzTDgzLjY2OTQgNDIuMjMwOEM3NC43MDA0IDQyLjE3MTQgNjAuNjc2NyA0MS43Mzg3IDUyLjA1MTQgNDIuMzMzN0w1MS45Nzc5IDAuNDk5MjY5TDgzLjAyNiAwLjQ4MjY2NloiIGZpbGw9IiMwNjRFM0IiLz4KPHBhdGggZD0iTTI3OS4zOTMgMC4xMzAxMDRDMjg2LjgxMiAtMC4zMjcyNjQgMjkwLjUxNSAwLjM0MDM0NCAyOTcuMjU5IDMuNDMzNDhDMjk2LjY1IDUuMzg2NTEgMjk1LjkwOCA2LjgzMzUgMjk1LjAyOSA4LjY1OTI5QzI4OS4zODUgNS40NTkxOSAyODAuMDA5IDIuODk0MjIgMjc0LjMzMiA3Ljc1ODkyQzI3MC41NDEgMTEuMDA5MiAyNzEuOTQgMTQuMjE5NiAyNzYuMTg1IDE2LjMyMTdDMjgzLjM5IDE5Ljg4OTEgMjk3LjU3NiAxOS4wNzU1IDI5OC44MjQgMjkuNjE0MUMyOTkuNjA3IDM5LjgyMDggMjkwLjQ0MSA0Mi42NzIgMjgxLjkxIDQyLjkzNTdDMjc2LjIxNCA0Mi45MjU3IDI2OS44OTUgNDEuMTA1OCAyNjUuNDcyIDM3LjQ0OEwyNjcuNDE0IDMyLjQ0MTNDMjczLjAwNiAzNi45OTkxIDI4NC45NDggNDAuMTA1MiAyOTAuMjE2IDM1Ljc0NzZDMjk4LjIzIDI4LjMyMDkgMjg2LjUxMyAyNC45MzUzIDI4MC43MjkgMjMuODQ0NkMyNzQuNjc1IDIyLjcwMjcgMjcwLjE3OSAyMC44NDgxIDI2Ni44MTYgMTUuODQ5NUMyNjUuNDU4IDUuNDk1NjkgMjY5Ljc1NCAxLjk1NTg5IDI3OS4zOTMgMC4xMzAxMDRaIiBmaWxsPSIjMDY0RTNCIi8+CjxwYXRoIGQ9Ik0yMDkuOTk4IDAuNDg1MzUyTDIxNS45ODIgMC41MDU2MzVMMjE1Ljg4OSA2LjQ4NTM0QzIxNS45MTUgMTMuNjg3OCAyMTYuMzU4IDIyLjY5NDYgMjE1LjQ5OCAyOS43ODI3QzIxMy42ODkgNDQuNzU0MSAxOTAuMTY4IDQ3LjQxNDMgMTgyLjMwMSAzNS43NDg0QzE4MS4wNSAzMy44ODcyIDE4MC4yMjMgMzEuNzcxNSAxNzkuODgzIDI5LjU1MzNDMTc5LjQ4NSAyNy4wOTIgMTc5LjE0MSAyLjg1ODEzIDE3OS41NyAwLjU1NjkwOEwxODUuNTQyIDAuNTQ5MTYzQzE4NS40NzYgOS4zNzMwNSAxODQuODQ4IDE5Ljk2ODkgMTg1Ljg5NyAyOC41MTU0QzE4Ny44ODYgNDAuOTAzIDIwOC4yMjIgNDAuNTUzNyAyMDkuNjU4IDI4LjM3MTlDMjEwLjY5OSAxOS41MjMzIDIxMC4wODMgOS4zNzg5NSAyMDkuOTk4IDAuNDg1MzUyWiIgZmlsbD0iIzA2NEUzQiIvPgo8cGF0aCBkPSJNODkuODc4OCAwLjQ5NDUwN0wxMjUuMzQ3IDAuNTI0MDA5TDEyNS4zMzQgNS43MjM2MUwxMTAuNzQxIDUuNzMyMUMxMTEuMDQ1IDEyLjUyMTggMTEwLjgxNyAyMS4wNDY5IDExMC44MjkgMjcuOTcwMUMxMTAuODE0IDMyLjc1MDcgMTEwLjgzNSAzNy41MzEgMTEwLjg5MyA0Mi4zMTEyQzEwOC42MjUgNDIuMTUgMTA2LjYzMiA0Mi4yMzA4IDEwNC4zNjggNDIuMjkyOEwxMDQuMzkzIDUuNzQyNzlDOTkuNTY3MiA1LjY3Nzg4IDk0Ljc0MDcgNS42OTI2NCA4OS45MTUzIDUuNzg3NDRMODkuODc4OCAwLjQ5NDUwN1oiIGZpbGw9IiMwNjRFM0IiLz4KPHBhdGggZD0iTTIyOS4yNiAwLjUxNjY1N0wyMzUuNjM4IDAuNDc0MjQzQzIzNS4zMSAxMi4wNTY3IDIzNS40ODcgMjUuMzEwOCAyMzUuNjI3IDM2Ljk0MTJDMjQzLjU0MiAzNi44NDYxIDI1MS42ODUgMzYuOTI2OCAyNTkuNjE0IDM2LjkyMjhDMjU5LjUzNiAzOC42NTIzIDI1OS41NjYgNDAuNTc2MiAyNTkuNTU5IDQyLjMyMjdDMjU2LjUyNCA0Mi4wMDkxIDI1MC42ODEgNDIuMTU4OSAyNDcuNTE0IDQyLjE1OTZDMjQxLjU5NiA0Mi4xNjA3IDIzNS4yNDcgNDIuMDYzIDIyOS4zNTkgNDIuMjg4N0MyMjkuNDE4IDI4LjgzNTEgMjI5LjY1NSAxMy44NzE0IDIyOS4yNiAwLjUxNjY1N1oiIGZpbGw9IiMwNjRFM0IiLz4KPC9zdmc+" class="brand-logo" alt="NetPulse">
    </div>
  </div>
  <div class="report-meta">
    <div class="report-title">System Log Report</div>
    <div class="report-ref">${refCode}</div>
    <div class="report-date">Generated ${dateStr} at ${timeStr}</div>
  </div>
</div>
<div class="summary">
  <div class="card"><div class="card-label">Total Entries</div><div class="card-val">${total}</div></div>
  <div class="card"><div class="card-label">Critical</div><div class="card-val r">${crit}</div></div>
  <div class="card"><div class="card-label">Warnings</div><div class="card-val a">${warn}</div></div>
  <div class="card"><div class="card-label">Info / Success</div><div class="card-val g">${info}</div></div>
</div>
<div class="section-hd">
  <span class="section-lbl">Log Entries</span>
  <span class="section-pill">${total} records</span>
</div>
<table>
  <thead><tr><th>Date</th><th>Time</th><th>Level</th><th>Device</th><th>Event</th><th>Source</th></tr></thead>
  <tbody>${tableRows}</tbody>
</table>
<div class="footer">
  <span>© 2026 <strong>NetPulse</strong> — Network Operations Center &mdash; Confidential &amp; Internal Use Only.</span>
  <span>Auto-generated by NetPulse NOC. Do not distribute externally.</span>
</div>
</div>
</body></html>`);
        win.document.close();
    }

    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('typeFilter').addEventListener('change', applyFilters);
    document.getElementById('severityFilter').addEventListener('change', applyFilters);
    document.getElementById('dateFrom').addEventListener('change', applyFilters);
    document.getElementById('dateTo').addEventListener('change', applyFilters);

    applyFilters();
    </script>
</body>
</html>