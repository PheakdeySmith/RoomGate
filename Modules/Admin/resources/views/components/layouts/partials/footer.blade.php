<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            @php
                $appTitle = $appSettings->app_name ?: 'RoomGate';
        $footerLogo = $appSettings->footer_logo_path ? asset($appSettings->footer_logo_path) : null;
            @endphp
            <div class="text-body d-flex align-items-center gap-2">
                @if ($footerLogo)
                    <img src="{{ $footerLogo }}" alt="{{ $appTitle }}" style="height: 20px;">
                @endif
                <span>{{ $appTitle }} Admin Console</span>
            </div>
        </div>
    </div>
</footer>
