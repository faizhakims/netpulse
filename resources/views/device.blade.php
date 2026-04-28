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
    </style>
</head>
<body>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main class="main">

        {{-- ── Header ── --}}
        <div class="header">
            <div>
                <h1 class="page-title">Devices</h1>
                <div class="sub-info">
                    <span class="dot"></span>
                    Auto-refresh in <span id="refreshCountdown">8</span>s
                    &nbsp;&nbsp;·&nbsp;&nbsp;
                    Last updated: <span id="lastUpdated">just now</span>
                </div>
            </div>
            <button class="btn" onclick="openAddDeviceModal()">
                <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                ADD DEVICE
            </button>
        </div>

        {{-- ── Device Grid ── --}}

        <div class="grid">

            @foreach($devices as $device)

            {{--
                LINK ke halaman detail:
                Ganti route('/device/' . $device->id) dengan
                route('device.show', $device->id) setelah pakai Eloquent model.
            --}}
            <a href="{{ route('device.show', $device->device) }}" class="card">

                <div class="card-header">
                    <span class="card-category">{{ strtolower($device->status) === 'up' ? 'Active' : 'Inactive' }}</span>

                    @if(strtolower($device->status) === 'up')
                        <span class="badge">Connected</span>
                    @elseif(strtolower($device->status) === 'warning')
                        <span class="badge warning">Warning</span>
                    @else
                        <span class="badge offline">Offline</span>
                    @endif
                </div>

                <div class="card-content">
                    <div>
                        <div class="card-title">{{ $device->device }}</div>
                        <div class="meta">
                            IP &nbsp;<span>{{ $device->ip_address }}</span><br>
                            LAT &nbsp;<span>{{ $device->latency_ms !== null ? $device->latency_ms . ' ms' : '—' }}</span><br>
                            UP &nbsp;&nbsp;<span>{{ $device->checked_at ? $device->checked_at->diffForHumans() : '—' }}</span><br>
                            LOC &nbsp;<span>{{ '-' }}</span>
                        </div>
                    </div>

                    {{--
                        Gambar device — gunakan $device->imageUrl() dari DB,
                        atau fallback ke gambar generik berdasarkan tipe.
                    --}}
                    <img
                        src="{{ $device->imageUrl() }}"
                        class="device-img"
                        alt="{{ $device->device }}"
                        onerror="this.src='{{ asset('images/router.png') }}'"
                    >
                </div>

            </a>

            @endforeach
            {{-- @endforeach (DB version) --}}

        </div>
    </main>

    <script>
    /* ── Auto-refresh countdown (UI only — replace with actual polling jika perlu) ── */
    (function () {
        let seconds = 8;
        const el = document.getElementById('refreshCountdown');
        const lastEl = document.getElementById('lastUpdated');

        if (!el) return;

        setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                seconds = 8;
                // TODO: fetch('/device/data') lalu update kartu secara dinamis
                lastEl.textContent = 'just now';
            }
            el.textContent = seconds;
        }, 1000);
    })();

    /* ── Add Device Modal (placeholder) ── */
    function openAddDeviceModal() {
        // TODO: ganti dengan modal Bootstrap / Alpine.js sesuai stack yang dipakai
        alert('Add Device modal — hubungkan ke form DeviceController@store');
    }
    </script>
</body>
</html>