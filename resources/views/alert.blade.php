<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse — Alert Configuration</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/alert.css') }}">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #F5F7F9;
            font-family: 'DM Sans', sans-serif;
            color: #2c2f31;
        }
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main">

        {{-- ── Page Header ── --}}
        <div class="page-header">
            <h1 class="page-title">Alert Configuration</h1>
            <p class="page-subtitle">Manage notification channels and threshold rules</p>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-label">Active Rules</span>
                <span class="stat-value">9</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Alert Sent (24H)</span>
                <span class="stat-value">9</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Success Rate</span>
                <span class="stat-value green">99.8<small style="font-size:18px;">%</small></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Failed Alerts</span>
                <span class="stat-value red">3</span>
            </div>
        </div>

        {{-- ── Settings Cards ── --}}
        <div class="settings-row">

            {{-- Telegram Settings --}}
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-title-group">
                        <div class="settings-card-icon">
                            <span class="material-symbols-outlined" style="font-size:18px;color:#2AABEE;">send</span>
                        </div>
                        <div>
                            <p class="settings-card-title">Telegram Settings</p>
                            <p class="settings-card-sub">Configure bot delivery</p>
                        </div>
                    </div>
                    <label class="toggle-wrap">
                        <input type="checkbox" checked>
                        <span class="toggle-track"></span>
                    </label>
                </div>

                <div class="field-grid">
                    <div>
                        <label class="field-label">Bot Token</label>
                        <input
                            class="field-input"
                            type="text"
                            value="1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ"
                            placeholder="Enter bot token"
                        >
                    </div>
                    <div>
                        <label class="field-label">Chat ID</label>
                        <input
                            class="field-input"
                            type="text"
                            value="-100123456789"
                            placeholder="Enter chat ID"
                        >
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn-reset">Reset</button>
                    <button class="btn-primary">
                        <span class="material-symbols-outlined" style="font-size:14px;">wifi_tethering</span>
                        Test Connection
                    </button>
                </div>
            </div>

            {{-- Email SMTP Settings --}}
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-title-group">
                        <div class="settings-card-icon">
                            <span class="material-symbols-outlined" style="font-size:18px;color:#F59E0B;">mail</span>
                        </div>
                        <div>
                            <p class="settings-card-title">Email SMTP Settings</p>
                            <p class="settings-card-sub">Configure email delivery</p>
                        </div>
                    </div>
                    <label class="toggle-wrap">
                        <input type="checkbox" checked>
                        <span class="toggle-track"></span>
                    </label>
                </div>

                <div class="field-grid">
                    <div class="field-grid-2">
                        <div>
                            <label class="field-label">SMTP Server</label>
                            <input class="field-input" type="text" value="smtp.mailgun.org" placeholder="SMTP Server">
                        </div>
                        <div>
                            <label class="field-label">Port</label>
                            <input class="field-input" type="text" value="587" placeholder="Port">
                        </div>
                    </div>
                    <div class="field-grid-2">
                        <div>
                            <label class="field-label">Username</label>
                            <input class="field-input" type="text" value="alerts@netpulse.io" placeholder="Username">
                        </div>
                        <div>
                            <label class="field-label">Password</label>
                            <input class="field-input" type="password" value="password123" placeholder="Password">
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn-reset">Reset</button>
                    <button class="btn-primary">
                        <span class="material-symbols-outlined" style="font-size:14px;">send</span>
                        Send Test Mail
                    </button>
                </div>
            </div>

        </div>

        {{-- ── Threshold Rules ── --}}
        <div class="section-header">
            <h2 class="section-title">Threshold Rules</h2>
            <button class="btn-add-rule">
                <span class="material-symbols-outlined" style="font-size:15px;">add</span>
                Add Rule
            </button>
        </div>

        {{--
            TODO: Replace dummy $rules with $thresholdRules from AlertController@index
            Expected $rule fields: id, severity, title, description, channels[], is_active
        --}}
        @php
        $rules = [
            [
                'severity'    => 'critical',
                'title'       => 'High Latency Alert',
                'description' => 'If Latency > 100ms for 5 mins',
                'channels'    => ['telegram', 'email'],
                'is_active'   => true,
            ],
            [
                'severity'    => 'warning',
                'title'       => 'Device Offline',
                'description' => 'If Device Status is DOWN',
                'channels'    => ['telegram'],
                'is_active'   => true,
            ],
            [
                'severity'    => 'warning',
                'title'       => 'High Bandwidth Usage',
                'description' => 'If Bandwidth > 90% for 15 mins',
                'channels'    => ['email'],
                'is_active'   => false,
            ],
        ];
        @endphp

        <div class="rules-grid">
            @foreach($rules as $rule)
            <div class="rule-card">
                <div class="rule-card-top">
                    <span class="severity-badge {{ $rule['severity'] }}">{{ strtoupper($rule['severity']) }}</span>
                    <label class="toggle-wrap">
                        <input type="checkbox" {{ $rule['is_active'] ? 'checked' : '' }}>
                        <span class="toggle-track"></span>
                    </label>
                </div>

                <p class="rule-title">{{ $rule['title'] }}</p>
                <p class="rule-desc">{{ $rule['description'] }}</p>

                <div class="rule-footer">
                    <div class="rule-channels">
                        @if(in_array('telegram', $rule['channels']))
                            <span class="material-symbols-outlined" title="Telegram" style="color:#2AABEE;">send</span>
                        @endif
                        @if(in_array('email', $rule['channels']))
                            <span class="material-symbols-outlined" title="Email" style="color:#F59E0B;">mail</span>
                        @endif
                    </div>
                    <div class="rule-actions">
                        <button class="icon-btn" title="Edit">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="icon-btn" title="Delete">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── Alert History ── --}}
        <div class="history-section">
            <div class="section-header">
                <h2 class="section-title">Alert History</h2>
                <a href="#" class="btn-view-all">
                    View All
                    <span class="material-symbols-outlined" style="font-size:15px;">arrow_forward</span>
                </a>
            </div>

            <div class="history-card">

                {{-- Filters --}}
                <div class="filters-bar">
                    <div class="search-wrap">
                        <span class="material-symbols-outlined">search</span>
                        <input class="search-input" type="text" placeholder="Search alerts...">
                    </div>

                    <select class="filter-select">
                        <option>Status: All</option>
                        <option>Sent</option>
                        <option>Failed</option>
                    </select>

                    <select class="filter-select">
                        <option>Channel: All</option>
                        <option>Telegram</option>
                        <option>Email</option>
                    </select>

                    <input class="date-input" type="date" placeholder="mm/dd/yyyy">

                    <div class="export-group">
                        <button class="btn-export">Export .CSV</button>
                        <button class="btn-export">Export .PDF</button>
                    </div>
                </div>

                {{-- Table --}}
                {{--
                    TODO: Replace dummy $history with $alertHistory from AlertController@index
                    Expected fields: time, channel, recipient, status ('sent'|'failed'), message
                --}}
                @php
                $history = [
                    [
                        'time'      => 'Today, 14:32',
                        'channel'   => 'telegram',
                        'recipient' => '-100123456789',
                        'status'    => 'sent',
                        'message'   => 'CRITICAL: Core-Router-01 is DOWN',
                    ],
                    [
                        'time'      => 'Today, 11:15',
                        'channel'   => 'email',
                        'recipient' => 'alerts@netpulse.io',
                        'status'    => 'sent',
                        'message'   => 'WARNING: High Latency (142ms) on Link-NYC',
                    ],
                    [
                        'time'      => 'Yesterday, 09:45',
                        'channel'   => 'telegram',
                        'recipient' => '-100123456789',
                        'status'    => 'failed',
                        'message'   => 'CRITICAL: Database sync failed on DB-02',
                    ],
                ];
                @endphp

                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Channel</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $log)
                        <tr>
                            <td>{{ $log['time'] }}</td>
                            <td>
                                <div class="channel-cell">
                                    @if($log['channel'] === 'telegram')
                                        <span class="channel-icon telegram material-symbols-outlined">send</span>
                                        Telegram
                                    @else
                                        <span class="channel-icon email material-symbols-outlined">mail</span>
                                        Email
                                    @endif
                                </div>
                            </td>
                            <td><span class="mono">{{ $log['recipient'] }}</span></td>
                            <td>
                                @if($log['status'] === 'sent')
                                    <span class="status-badge sent">
                                        <span class="material-symbols-outlined">check_circle</span>
                                        Sent
                                    </span>
                                @else
                                    <span class="status-badge failed">
                                        <span class="material-symbols-outlined">cancel</span>
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td>{{ $log['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>

        {{-- ── Footer ── --}}
        <div class="page-footer">
            <span class="footer-copy">© 2026 NetPulse - Network Operations Center</span>
            <div class="footer-status">
                <div class="status-item">
                    <span class="status-dot"></span>
                    API Status: Operational
                </div>
                <div class="status-item">
                    <span class="status-dot"></span>
                    Database: 4ms Sync
                </div>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">System Logs</a>
            </div>
        </div>

    </main>

</body>
</html>