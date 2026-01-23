<aside id="layout-menu" class="layout-menu menu-vertical menu">
    @php
        $brandName = $appSettings->app_short_name ?: ($appSettings->app_name ?: 'RoomGate');
        $lightLogo = $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : null;
        $darkLogo = $appSettings->logo_dark_path ? asset($appSettings->logo_dark_path) : $lightLogo;
        $smallLogo = $appSettings->logo_small_path ? asset($appSettings->logo_small_path) : ($lightLogo ?? $darkLogo);
    @endphp
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                @if ($lightLogo || $darkLogo || $smallLogo)
                    <img
                        src="{{ $lightLogo ?? $darkLogo ?? $smallLogo }}"
                        alt="{{ $brandName }}"
                        class="img-fluid app-brand-img"
                        style="height: 26px;">
                    <img
                        src="{{ $smallLogo ?? $lightLogo ?? $darkLogo }}"
                        alt="{{ $brandName }}"
                        class="img-fluid app-brand-img-collapsed"
                        style="height: 26px;">
                @else
                    <span class="text-primary">
                        <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                                fill="currentColor" />
                            <path
                                opacity="0.06"
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                                fill="#161616" />
                            <path
                                opacity="0.06"
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                                fill="#161616" />
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                                fill="currentColor" />
                        </svg>
                    </span>
                @endif
            </span>
            <span class="app-brand-text demo text-heading fw-bold">{{ $brandName }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="icon-base ti tabler-x d-block d-xl-none"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-layout-dashboard"></i>
                <div data-i18n="menu.dashboard">Dashboard</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Access Control</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
            <a href="{{ route('admin.roles') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-shield-lock"></i>
                <div data-i18n="menu.roles">Roles</div>
            </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.permissions') ? 'active' : '' }}">
            <a href="{{ route('admin.permissions') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-lock-access"></i>
                <div data-i18n="menu.permissions">Permissions</div>
            </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.tenants.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-users"></i>
                <div data-i18n="menu.tenants">Tenants</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.tenants.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.tenants.index') }}" class="menu-link">
                        <div data-i18n="menu.tenants_list">List</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
            <a href="{{ route('admin.audit-logs') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-history"></i>
                <div data-i18n="menu.audit_logs">Audit Logs</div>
            </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.translations') ? 'active' : '' }}">
            <a href="{{ route('admin.translations') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-language"></i>
                <div data-i18n="menu.translations">Translations</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">System</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
            <a href="{{ route('admin.plans.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-credit-card"></i>
                <div data-i18n="menu.plans">Plans</div>
            </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.subscriptions.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-receipt-2"></i>
                <div data-i18n="menu.subscriptions">Subscriptions</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.subscriptions.index') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
                        <div data-i18n="menu.subscriptions_list">Subscriptions</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.subscriptions.invoices') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.invoices') }}" class="menu-link">
                        <div data-i18n="menu.subscription_invoices">Invoices</div>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.subscriptions.payments') ? 'active' : '' }}">
                    <a href="{{ route('admin.subscriptions.payments') }}" class="menu-link">
                        <div data-i18n="menu.subscription_payments">Payments</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <a href="{{ route('admin.settings') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-settings"></i>
                <div data-i18n="menu.settings">Settings</div>
            </a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.properties.index') ? 'active' : '' }}">
            <a href="{{ route('admin.properties.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-building-community"></i>
                <div data-i18n="menu.properties">Properties</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Tenant Area</span>
        </li>
        <li class="menu-item">
            <a href="{{ url('/core/crm-dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-building-community"></i>
                <div data-i18n="menu.tenant_dashboard">Tenant Dashboard</div>
            </a>
        </li>
    </ul>
</aside>
