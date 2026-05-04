<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPulse — Devices</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/device.css') }}">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f5f7f9;
            font-family: 'DM Sans', sans-serif;
            color: #2c2f31;
        }

        /* ── Add Device Button ── */
        .btn-add-device {
            background: var(--green, #006947);
            color: #d1fae5;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.15s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-add-device:hover {
            background: var(--green-dim, #005c3e);
            color: #d1fae5;
        }
        .btn-add-device .material-symbols-outlined {
            font-size: 18px;
            font-variation-settings: 'FILL' 1, 'wght' 500;
        }

        /* ── Modal Overlay ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }

        /* ── Modal Box ── */
        .modal-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            animation: modalIn 0.2s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(-16px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .modal-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #064E3B;
            letter-spacing: -0.03em;
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 4px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            transition: color 0.15s, background 0.15s;
        }
        .modal-close:hover {
            color: #2c2f31;
            background: #f1f5f9;
        }

        /* ── Form Fields ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .form-input,
        .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.875rem;
            font-family: 'DM Sans', sans-serif;
            color: #2c2f31;
            background: #f8fafc;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
            width: 100%;
        }
        .form-input:focus,
        .form-select:focus {
            border-color: #006947;
            box-shadow: 0 0 0 3px rgba(0, 105, 71, 0.12);
            background: #ffffff;
        }
        .form-input.input-error {
            border-color: #ef4444;
        }
        .form-hint {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* ── Modal Footer ── */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.15s;
        }
        .btn-cancel:hover { background: #e2e8f0; }

        .btn-submit {
            background: var(--green, #006947);
            color: #d1fae5;
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 0.85rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
            letter-spacing: 0.02em;
        }
        .btn-submit:hover { background: var(--green-dim, #005c3e); }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ── Toast Notification ── */
        .toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #064E3B;
            color: #d1fae5;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            z-index: 2000;
            transform: translateY(80px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            max-width: 360px;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.error {
            background: #dc2626;
            color: #fff;
        }
        .toast .material-symbols-outlined {
            font-size: 20px;
            font-variation-settings: 'FILL' 1;
        }

        /* ── Spinner ── */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2.5px solid rgba(209, 250, 229, 0.4);
            border-top-color: #d1fae5;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main">

        {{-- ── Header ── --}}
        <div class="header">
            <div>
                <h1 class="page-title" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700; letter-spacing: -0.05em; margin: 0;">
                    Devices
                </h1>
            </div>

            {{-- Tombol Add Device --}}
            <button class="btn-add-device" onclick="openAddModal()">
                <span class="material-symbols-outlined">add</span>
                Add Device
            </button>
        </div>

        {{-- ── Device Grid ── --}}
        <div class="grid" id="device-grid">

            @foreach($devices as $device)

            <a href="{{ route('device.show', $device->device) }}" class="card">

                @php
                    $effStatus = $device->effectiveStatus();
                    $isUp = $effStatus === 'up';

                    if ($isUp && $device->last_down_at) {
                        $uptimeSeconds = now()->diffInSeconds(\Carbon\Carbon::parse($device->last_down_at));
                    } elseif (!$isUp && $device->last_up_at) {
                        $lastOnlineSeconds = now()->diffInSeconds(\Carbon\Carbon::parse($device->last_up_at));
                    } else {
                        $uptimeSeconds = null;
                        $lastOnlineSeconds = null;
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

    {{-- ── Modal Add Device ── --}}
    <div class="modal-overlay" id="add-device-modal" onclick="closeOnOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">Add New Device</h2>
                <button class="modal-close" onclick="closeAddModal()">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="form-grid">

                {{-- Nama Device --}}
                <div class="form-group">
                    <label class="form-label">Device Name *</label>
                    <input type="text" id="f-name" class="form-input"
                           placeholder="e.g. main-router"
                           autocomplete="off">
                    <span class="form-hint">Lowercase, no spaces (use - or _)</span>
                </div>

                {{-- IP Address --}}
                <div class="form-group">
                    <label class="form-label">IP Address *</label>
                    <input type="text" id="f-ip" class="form-input"
                           placeholder="e.g. 192.168.99.1"
                           autocomplete="off">
                </div>

                {{-- Tipe Device --}}
                <div class="form-group">
                    <label class="form-label">Device Type *</label>
                    <select id="f-type" class="form-select">
                        <option value="">— Select type —</option>
                        <option value="mikrotik">MikroTik CHR</option>
                        <option value="openwrt">OpenWRT</option>
                        <option value="linux">Linux Server</option>
                    </select>
                </div>

                {{-- SNMP Community --}}
                <div class="form-group">
                    <label class="form-label">SNMP Community</label>
                    <input type="text" id="f-snmp" class="form-input"
                           placeholder="public" value="public"
                           autocomplete="off">
                </div>

                {{-- SSH User --}}
                <div class="form-group">
                    <label class="form-label">SSH User</label>
                    <input type="text" id="f-ssh-user" class="form-input"
                           placeholder="admin" value="admin"
                           autocomplete="off">
                </div>

                {{-- SSH Password --}}
                <div class="form-group">
                    <label class="form-label">SSH Password</label>
                    <input type="password" id="f-ssh-pass" class="form-input"
                           placeholder="Leave blank if none"
                           autocomplete="new-password">
                </div>

                {{-- Deskripsi --}}
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

    {{-- ── Toast ── --}}
    <div class="toast" id="toast">
        <span class="material-symbols-outlined" id="toast-icon">check_circle</span>
        <span id="toast-msg">Device added successfully</span>
    </div>

    <script>
        const MONITORING_API = '{{ config('services.monitoring.url', '') }}';

        // ── Modal ─────────────────────────────────────────────────────────────

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

        // ── Validation ────────────────────────────────────────────────────────

        function validateForm() {
            const name = document.getElementById('f-name').value.trim();
            const ip   = document.getElementById('f-ip').value.trim();
            const type = document.getElementById('f-type').value;

            let valid = true;

            // Nama: huruf kecil, angka, - _
            if (!name || !/^[a-z0-9_-]+$/.test(name)) {
                document.getElementById('f-name').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-name').classList.remove('input-error');
            }

            // IP Address format
            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ip || !ipRegex.test(ip)) {
                document.getElementById('f-ip').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-ip').classList.remove('input-error');
            }

            // Tipe wajib dipilih
            if (!type) {
                document.getElementById('f-type').classList.add('input-error');
                valid = false;
            } else {
                document.getElementById('f-type').classList.remove('input-error');
            }

            return valid;
        }

        // ── Submit ────────────────────────────────────────────────────────────

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

                    // Reload halaman setelah 1.5 detik agar card baru muncul
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

        // ── Loading State ─────────────────────────────────────────────────────

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

        // ── Toast ─────────────────────────────────────────────────────────────

        function showToast(msg, type = 'success') {
            const toast    = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            const toastIcon= document.getElementById('toast-icon');

            toastMsg.textContent  = msg;
            toastIcon.textContent = type === 'success' ? 'check_circle' : 'error';
            toast.className       = `toast ${type === 'error' ? 'error' : ''}`;

            // Show
            requestAnimationFrame(() => toast.classList.add('show'));

            // Hide after 4s
            setTimeout(() => toast.classList.remove('show'), 4000);
        }

        // ── Keyboard shortcut: Escape tutup modal ─────────────────────────────
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAddModal();
        });
    </script>

</body>
</html>
