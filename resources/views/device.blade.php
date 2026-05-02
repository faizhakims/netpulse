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
                <h1 class="page-title" style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700; letter-spacing: -0.05em; margin: 0;">
                    Devices
                </h1>
            </div>
            {{-- Tombol ADD DEVICE sudah dihapus --}}
        </div>

        {{-- ── Device Grid ── --}}

        <div class="grid">

            @foreach($devices as $device)

            <a href="{{ route('device.show', $device->device) }}" class="card">

                @php
                    $effStatus = $device->effectiveStatus();
                    $isUp = $effStatus === 'up';

                    // Hitung uptime / last online
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

</body>
</html>