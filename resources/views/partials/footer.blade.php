{{-- ===================== Footer ===================== --}}
<link rel="stylesheet" href="{{ asset('css/footer.css') }}?v={{ filemtime(public_path('css/footer.css')) }}">
<div class="page-footer">
    <span class="footer-copy">&copy; 2026 NetPulse – Network Operations Center</span>
    <div class="footer-status">
        <span class="footer-indicator">API Status: Operational</span>
        <span class="footer-indicator">Database: Live</span>
        <a href="{{ route('privacy') }}" class="footer-link">Privacy Policy</a>
        <a href="{{ route('logs') }}" class="footer-link">System Logs</a>
    </div>
</div>
