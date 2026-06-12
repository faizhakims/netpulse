{{-- Tombol Hamburger --}}
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
    <span class="material-symbols-outlined" id="sidebarToggleIcon">menu</span>
</button>

{{-- Overlay Backdrop --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- Sidebar Utama --}}
<aside class="sidebar" id="sidebar">

    <div class="sidebar-body">
        <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="nav-item-label">Overview</span>
        </a>

        <a href="/device" class="nav-item {{ request()->is('device') || request()->is('device/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">router</span>
            <span class="nav-item-label">Devices</span>
        </a>

        <a href="/traffic" class="nav-item {{ request()->is('traffic') || request()->is('traffic/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">analytics</span>
            <span class="nav-item-label">Traffic</span>
        </a>

        <span class="sidebar-section-label">Monitoring</span>

        <a href="/incidents" class="nav-item {{ request()->is('incidents') || request()->is('incidents/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">E911_Emergency</span>
            <span class="nav-item-label">Incidents</span>
        </a>

        <a href="/alert" class="nav-item {{ request()->is('alert') || request()->is('alert/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">notifications</span>
            <span class="nav-item-label">Alerts</span>
        </a>

        <span class="sidebar-section-label">System</span>

        <a href="/logs" class="nav-item {{ request()->is('logs') || request()->is('logs/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">list_alt</span>
            <span class="nav-item-label">Logs</span>
        </a>

        @can('manage settings')
        <a href="/settings" class="nav-item {{ request()->is('settings') || request()->is('settings/*') ? 'active' : '' }}">
            <span class="material-symbols-outlined">settings</span>
            <span class="nav-item-label">Settings</span>
        </a>
        @endcan

    </div>

    <div class="sidebar-footer">
        <a href="#"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="nav-item">
            <span class="material-symbols-outlined">logout</span>
            <span class="nav-item-label">Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </div>
</aside>

<script>
(function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');
    const icon = document.getElementById('sidebarToggleIcon');

    function openSidebar() {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('visible');
        if (icon) icon.textContent = 'close';
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('visible');
        if (icon) icon.textContent = 'menu';
        document.body.style.overflow = '';
    }

    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.contains('mobile-open') ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Tutup sidebar saat klik item navigasi
    sidebar.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
            closeSidebar();
        }
    });
})();
</script>