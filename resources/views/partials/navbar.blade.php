<nav class="topbar">
    <div class="topbar-left">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('images/netpulseHijau.svg') }}" alt="NetPulse" class="logo-img">
        </a>
    </div>

    <div class="topbar-right">
        <button class="nav-icon-btn" title="Refresh Page" onclick="window.location.reload()">
            <span class="material-symbols-outlined">refresh</span>
        </button>

        {{-- 2. Cloud Status (Read-Only) --}}
        <div class="nav-icon-btn cloud-status-indicator" title="Cloud Synced">
            <span class="material-symbols-outlined">cloud_done</span>
        </div>

        {{-- 3. Alert Menu --}}
        <div class="dropdown-wrapper" id="alert-wrapper">
            <button class="nav-icon-btn" id="alert-btn" title="Alerts">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <div class="dropdown-menu dropdown-alerts" id="alert-dropdown">
                <div class="dropdown-header">Recent Alerts</div>
                <div class="dropdown-items">
                    @php
                        $recentAlerts = \App\Models\AlertHistory::with('rule')->latest('sent_at')->take(3)->get();
                    @endphp
                    @forelse($recentAlerts as $alert)
                        <div class="dropdown-item alert-item">
                            <div class="alert-title">{{ $alert->rule->title ?? 'System Alert' }}</div>
                            <div class="alert-desc">{{ Str::limit($alert->message, 50) }}</div>
                            <div class="alert-time">{{ $alert->sent_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="dropdown-empty">No recent alerts</div>
                    @endforelse
                </div>
                <div class="dropdown-footer">
                    <a href="{{ route('alert') }}" class="dropdown-footer-link">View Alerts</a>
                </div>
            </div>
        </div>

        {{-- 4. User Profile (Nickname) --}}
        <div class="dropdown-wrapper" id="user-wrapper">
            <button class="user-btn" id="user-btn">
                <span class="username">{{ Auth::user()->name ?? 'Admin' }}</span>
                <span class="material-symbols-outlined icon-caret">expand_more</span>
            </button>
            <div class="dropdown-menu dropdown-user" id="user-dropdown">
                <div class="dropdown-item user-info-item">
                    <span class="material-symbols-outlined">person</span>
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">{{ Auth::user()->name ?? 'Admin' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;">{{ Auth::user()->currentRoleName() }}</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                @can('manage settings')
                <a href="{{ route('settings') }}" class="dropdown-item">
                    <span class="material-symbols-outlined">settings</span>
                    Settings
                </a>
                @endcan
                <form action="{{ route('logout') }}" method="POST" class="dropdown-item-form">
                    @csrf
                    <button type="submit" class="dropdown-item dropdown-item-btn">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- JavaScript untuk interaksi dropdown --}}
<script>
(function() {
    function initNavbarDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown-wrapper');
        dropdowns.forEach(wrapper => {
            const btn = wrapper.querySelector('button');
            const menu = wrapper.querySelector('.dropdown-menu');
            if (!btn || !menu || btn.dataset.init) return;
            btn.dataset.init = 'true';

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdowns.forEach(other => {
                    if (other !== wrapper) {
                        other.querySelector('.dropdown-menu')?.classList.remove('show');
                        other.querySelector('button')?.classList.remove('active');
                    }
                });
                menu.classList.toggle('show');
                btn.classList.toggle('active');
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-wrapper')) {
                dropdowns.forEach(wrapper => {
                    const menu = wrapper.querySelector('.dropdown-menu');
                    const btn = wrapper.querySelector('button');
                    if (menu && menu.classList.contains('show')) {
                        menu.classList.remove('show');
                        if (btn) btn.classList.remove('active');
                    }
                });
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbarDropdowns);
    } else {
        initNavbarDropdowns();
    }
})();
</script>