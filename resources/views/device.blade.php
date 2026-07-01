<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse — Devices</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/device.css') }}">
    <script type="application/ld+json">
    {
        "@@context": {
            "schema":   "https://schema.org/",
            "netpulse": "http://netpulse.local/ontology#"
        },
        "@@type": "schema:WebPage",
        "@@id": "{{ url('/device') }}",
        "schema:name": "NetPulse — Network Devices",
        "schema:description": "List of all monitored network devices including routers and switches, with real-time availability status.",
        "schema:url": "{{ url('/device') }}",
        "schema:mainEntity": {
            "@@type": "schema:ItemList",
            "schema:name": "Monitored Network Devices",
            "schema:numberOfItems": {{ $devices->count() }},
            "schema:itemListElement": [
                @foreach($devices as $i => $device)
                {
                    "@@type": "schema:ListItem",
                    "schema:position": {{ $i + 1 }},
                    "schema:item": {
                        "@@type": ["schema:ComputerServer", "netpulse:NetworkDevice"],
                        "@@id": "{{ url('/api/rdf/devices/' . ($device->device->name ?? '')) }}",
                        "schema:name": "{{ addslashes($device->device->name ?? 'Unknown') }}",
                        "schema:description": "{{ addslashes($device->device->type ?? 'Network Device') }} — Layer {{ addslashes($device->device->layer ?? 'N/A') }}",
                        "schema:operatingStatus": "{{ $device->effectiveStatus() === 'up' ? 'https://schema.org/InStock' : ($device->effectiveStatus() === 'down' ? 'https://schema.org/Discontinued' : 'https://schema.org/LimitedAvailability') }}",
                        "netpulse:ipAddress": "{{ $device->ip_address ?? '' }}",
                        "netpulse:status": "{{ $device->effectiveStatus() }}"
                    }
                }{{ !$loop->last ? ',' : '' }}
                @endforeach
            ],
            "schema:sameAs": "{{ url('/api/rdf/devices') }}"
        }
    }
    </script>
</head>

<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main">
        <div class="header">
            <div>
                <h1 class="page-title" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700; letter-spacing: -0.05em; margin: 0;">
                    Devices
                </h1>
            </div>

            @can('manage devices')
            <button class="btn-add-device" onclick="openAddModal()">
                <span class="material-symbols-outlined">add</span>
                Add Device
            </button>
            @endcan
        </div>

        <div class="grid" id="device-grid">

            @foreach($devices as $device)

            <a href="{{ route('device.show', $device->device) }}" class="card">

                @php
                    $effStatus = $device->effectiveStatus();
                    $isUp = $effStatus === 'up';

                    // Reset setiap iterasi agar tidak carry-over antar device
                    $uptimeSeconds     = null;
                    $lastOnlineSeconds = null;

                    if ($isUp) {
                        if ($device->last_down_at) {
                            // Uptime sejak terakhir kali down
                            $uptimeSeconds = now()->diffInSeconds(\Carbon\Carbon::parse($device->last_down_at));
                        } else {
                            // Belum pernah down — ambil checked_at record pertama sebagai proxy start
                            $firstCheck = \Illuminate\Support\Facades\DB::table('device_status')
                                ->where('device_id', $device->device_id)
                                ->orderBy('checked_at')
                                ->value('checked_at');
                            $uptimeSeconds = $firstCheck
                                ? now()->diffInSeconds(\Carbon\Carbon::parse($firstCheck))
                                : null;
                        }
                    } elseif ($device->last_up_at) {
                        $lastOnlineSeconds = now()->diffInSeconds(\Carbon\Carbon::parse($device->last_up_at));
                    }
                @endphp

                <div class="card-header">
                    <span class="card-category">
                        {{ $effStatus === 'up' ? 'Active' : ($effStatus === 'unknown' ? 'Unknown' : 'Inactive') }}
                    </span>

                    @if($effStatus === 'up')
                        <span class="badge">Connected</span>
                    @elseif($effStatus === 'unknown')
                        <span class="badge" style="background:#f59e0b;color:#fff;" title="Data terlalu lama — collector mungkin mati">Unknown</span>
                    @else
                        <span class="badge offline">Offline</span>
                    @endif
                </div>

                <div class="card-content">
                    <div>
                        <div class="card-title">{{ $device->device }}</div>
                        <div class="meta">
                            IP &nbsp;<span>{{ $device->ip_address }}</span><br>
                            LAT &nbsp;<span>
                                {{ $isUp && $device->latency_ms !== null ? $device->latency_ms . ' ms' : '-' }}
                            </span><br>
                            @if($isUp)
                                Uptime &nbsp;&nbsp;<span>
                                    {{ $uptimeSeconds !== null
                                        ? \App\Models\DeviceStatus::formatDuration($uptimeSeconds)
                                        : 'Uptime unavailable' }}
                                </span><br>
                            @else
                                Last Online &nbsp;<span>
                                    {{ $lastOnlineSeconds !== null
                                        ? \App\Models\DeviceStatus::formatDuration($lastOnlineSeconds) . ' ago'
                                        : '–' }}
                                </span><br>
                            @endif
                            LOC &nbsp;<span>{{ '-' }}</span>
                        </div>
                    </div>

                    <img
                        src="{{ $device->imageUrl() }}"
                        class="device-img"
                        alt="{{ $device->device }}"
                        onerror="this.src='{{ asset('images/router.png') }}'"
                    >
                </div>

            </a>

            @endforeach

        </div>
    </main>

    <div class="modal-overlay" id="add-device-modal" onclick="closeOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">Add New Device</h2>
                <button class="modal-close" onclick="closeAddModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="form-grid">


                <div class="form-group">
                    <label class="form-label">Device Name *</label>
                    <input type="text" id="f-name" class="form-input"
                           placeholder="e.g. main-router"
                           autocomplete="off">
                    <span class="form-hint">Lowercase, no spaces (use - or _)</span>
                </div>


                <div class="form-group">
                    <label class="form-label">IP Address *</label>
                    <input type="text" id="f-ip" class="form-input"
                           placeholder="e.g. 192.168.99.1"
                           autocomplete="off">
                </div>


                <div class="form-group">
                    <label class="form-label">Device Type *</label>
                    <select id="f-type" class="form-select">
                        <option value="">— Select type —</option>
                        <option value="mikrotik">MikroTik CHR</option>
                        <option value="openwrt">OpenWRT</option>
                        <option value="linux">Linux Server</option>
                    </select>
                </div>


                <div class="form-group">
                    <label class="form-label">SNMP Community</label>
                    <input type="text" id="f-snmp" class="form-input"
                           placeholder="public" value="public"
                           autocomplete="off">
                </div>


                <div class="form-group">
                    <label class="form-label">SSH User</label>
                    <input type="text" id="f-ssh-user" class="form-input"
                           placeholder="admin" value="admin"
                           autocomplete="off">
                </div>


                <div class="form-group">
                    <label class="form-label">SSH Password</label>
                    <input type="password" id="f-ssh-pass" class="form-input"
                           placeholder="Leave blank if none"
                           autocomplete="new-password">
                </div>


                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <input type="text" id="f-desc" class="form-input"
                           placeholder="Optional description"
                           autocomplete="off">
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button class="btn-submit" id="btn-submit" onclick="submitAddDevice()">
                    <div class="spinner" id="submit-spinner"></div>
                    <span class="material-symbols-outlined" id="submit-icon"
                          style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 500;">
                        add_circle
                    </span>
                    <span id="submit-label">Add Device</span>
                </button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">
        <span class="material-symbols-outlined" id="toast-icon">check_circle</span>
        <span id="toast-msg">Device added successfully</span>
    </div>

    <script>
        const MONITORING_API = '{{ config('services.monitoring.url', '') }}';


        function openAddModal() {
            document.getElementById('add-device-modal').classList.add('active');
            document.getElementById('f-name').focus();
        }

        function closeAddModal() {
            document.getElementById('add-device-modal').classList.remove('active');
            resetForm();
        }

        function closeOnOverlay(e) {
            if (e.target === document.getElementById('add-device-modal')) {
                closeAddModal();
            }
        }

        function resetForm() {
            ['f-name','f-ip','f-type','f-snmp','f-ssh-user','f-ssh-pass','f-desc'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = id === 'f-snmp' ? 'public' : (id === 'f-ssh-user' ? 'admin' : '');
                    el.classList.remove('input-error');
                }
            });
            setLoading(false);
        }


        function validateForm() {
            const name = document.getElementById('f-name').value.trim();
            const ip   = document.getElementById('f-ip').value.trim();
            const type = document.getElementById('f-type').value;

            let valid = true;


            if (!name || !/^[a-z0-9_-]+$/.test(name)) {
                document.getElementById('f-name').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-name').classList.remove('input-error');
            }


            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ip || !ipRegex.test(ip)) {
                document.getElementById('f-ip').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-ip').classList.remove('input-error');
            }


            if (!type) {
                document.getElementById('f-type').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-type').classList.remove('input-error');
            }

            return valid;
        }


        async function submitAddDevice() {
            if (!validateForm()) {
                showToast('Please fill in all required fields correctly.', 'error');
                return;
            }

            if (!MONITORING_API) {
                showToast('MONITORING_API_URL not configured.', 'error');
                return;
            }

            const payload = {
                name:           document.getElementById('f-name').value.trim(),
                ip_address:     document.getElementById('f-ip').value.trim(),
                type:           document.getElementById('f-type').value,
                snmp_community: document.getElementById('f-snmp').value.trim() || 'public',
                ssh_user:       document.getElementById('f-ssh-user').value.trim() || 'admin',
                ssh_pass:       document.getElementById('f-ssh-pass').value,
                description:    document.getElementById('f-desc').value.trim(),
            };

            setLoading(true);

            try {
                const res = await fetch(`${MONITORING_API}/api/devices`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (res.ok && data.status === 'ok') {
                    closeAddModal();
                    showToast(`Device "${payload.name}" added! Monitoring will start on the next cycle.`, 'success');

                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    const msg = data.detail || data.message || 'Failed to add device.';
                    showToast(msg, 'error');
                    setLoading(false);
                }
            } catch (err) {
                showToast('Cannot connect to monitoring API: ' + err.message, 'error');
                setLoading(false);
            }
        }


        function setLoading(state) {
            const btn     = document.getElementById('btn-submit');
            const spinner = document.getElementById('submit-spinner');
            const icon    = document.getElementById('submit-icon');
            const label   = document.getElementById('submit-label');

            btn.disabled         = state;
            spinner.style.display = state ? 'block' : 'none';
            icon.style.display    = state ? 'none'  : 'inline';
            label.textContent     = state ? 'Adding...' : 'Add Device';
        }


        function showToast(msg, type = 'success') {
            const toast    = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            const toastIcon= document.getElementById('toast-icon');

            toastMsg.textContent  = msg;
            toastIcon.textContent = type === 'success' ? 'check_circle' : 'error';
            toast.className       = `toast ${type === 'error' ? 'error' : ''}`;


            requestAnimationFrame(() => toast.classList.add('show'));


            setTimeout(() => toast.classList.remove('show'), 4000);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAddModal();
        });
    </script>

</body>
</html>
