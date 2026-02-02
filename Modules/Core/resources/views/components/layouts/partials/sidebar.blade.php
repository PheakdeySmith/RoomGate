<!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu">
                @php
                    $brandName = $appSettings->app_short_name ?: ($appSettings->app_name ?: 'RoomGate');
        $lightLogo = $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : null;
        $darkLogo = $appSettings->logo_dark_path ? asset($appSettings->logo_dark_path) : $lightLogo;
        $smallLogo = $appSettings->logo_small_path ? asset($appSettings->logo_small_path) : ($lightLogo ?? $darkLogo);
                @endphp
                <div class="app-brand demo ">
                    <a href="index.html" class="app-brand-link">
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
                                    <svg width="32" height="22" viewBox="0 0 32 22" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                                            fill="currentColor" />
                                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                                            d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                                            fill="#161616" />
                                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                                            d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                                            fill="#161616" />
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                            @endif
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold ms-3">{{ $brandName }}</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                        <i class="icon-base ti tabler-x d-block d-xl-none"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item {{ request()->routeIs('Core.index', 'Core.crm') ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-smart-home"></i>
                            <div data-i18n="Dashboards">Dashboards</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('Core.index') ? 'active' : '' }}">
                                <a href="{{ route('Core.index') }}" class="menu-link">
                                    <div data-i18n="Analytics">Analytics</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.crm') ? 'active' : '' }}">
                                <a href="{{ route('Core.crm') }}" class="menu-link">
                                    <div data-i18n="CRM">CRM</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text" data-i18n="menu.operations">Operations</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.properties.*') ? 'active' : '' }}">
                        <a href="{{ route('core.properties.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-building-community"></i>
                            <div data-i18n="menu.properties">Properties</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.rooms.*') ? 'active' : '' }}">
                        <a href="{{ route('core.rooms.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-door"></i>
                            <div data-i18n="menu.rooms">Rooms</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.room-types.*') ? 'active' : '' }}">
                        <a href="{{ route('core.room-types.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-layout-grid"></i>
                            <div data-i18n="menu.room_types">Room Types</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.amenities.*') ? 'active' : '' }}">
                        <a href="{{ route('core.amenities.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-bulb"></i>
                            <div data-i18n="menu.amenities">Amenities</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.contracts.*') ? 'active' : '' }}">
                        <a href="{{ route('core.contracts.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-file-text"></i>
                            <div data-i18n="menu.contracts">Contracts</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.maintenance.*') ? 'active' : '' }}">
                        <a href="{{ route('core.maintenance.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-tool"></i>
                            <div data-i18n="menu.maintenance">Maintenance</div>
                        </a>
                    </li>

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text" data-i18n="menu.people">People</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.tenant-members.*') ? 'active' : '' }}">
                        <a href="{{ route('core.tenant-members.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-users"></i>
                            <div data-i18n="menu.tenant_members">Tenant Members</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('Core.users.*') ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div data-i18n="Users">Users</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('Core.users.index') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.index') }}" class="menu-link">
                                    <div data-i18n="User List">User List</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.users.account') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.account') }}" class="menu-link">
                                    <div data-i18n="Account">Account</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.users.billing') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.billing') }}" class="menu-link">
                                    <div data-i18n="Billing">Billing</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.users.connections') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.connections') }}" class="menu-link">
                                    <div data-i18n="Connections">Connections</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.users.notifications') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.notifications') }}" class="menu-link">
                                    <div data-i18n="Notifications">Notifications</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.users.security') ? 'active' : '' }}">
                                <a href="{{ route('Core.users.security') }}" class="menu-link">
                                    <div data-i18n="Security">Security</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text" data-i18n="menu.billing">Billing</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('Core.invoices.*') ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-file-invoice"></i>
                            <div data-i18n="Invoices">Invoices</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('Core.invoices.index') ? 'active' : '' }}">
                                <a href="{{ route('Core.invoices.index') }}" class="menu-link">
                                    <div data-i18n="List">List</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.invoices.add') ? 'active' : '' }}">
                                <a href="{{ route('Core.invoices.add') }}" class="menu-link">
                                    <div data-i18n="Add">Add</div>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text" data-i18n="menu.utilities">Utilities</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.utility-bills.*') ? 'active' : '' }}">
                        <a href="{{ route('core.utility-bills.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-file-invoice"></i>
                            <div data-i18n="menu.utility_bills">Bills</div>
                        </a>
                    </li>

                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text" data-i18n="menu.system">System</span>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.templates.*') ? 'active' : '' }}">
                        <a href="{{ route('core.templates.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-template"></i>
                            <div data-i18n="menu.message_templates">Message Templates</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('core.audit-logs.*') ? 'active' : '' }}">
                        <a href="{{ route('core.audit-logs.index') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-history"></i>
                            <div data-i18n="menu.audit_logs">Audit Logs</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('Core.access-roles', 'Core.access-permission') ? 'active open' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-lock"></i>
                            <div data-i18n="Access">Access</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item {{ request()->routeIs('Core.access-roles') ? 'active' : '' }}">
                                <a href="{{ route('Core.access-roles') }}" class="menu-link">
                                    <div data-i18n="menu.roles">Roles</div>
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('Core.access-permission') ? 'active' : '' }}">
                                <a href="{{ route('Core.access-permission') }}" class="menu-link">
                                    <div data-i18n="menu.permissions">Permissions</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </aside>

            <div class="menu-mobile-toggler d-xl-none rounded-1">
                <a href="javascript:void(0);"
                    class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                    <i class="ti tabler-menu icon-base"></i>
                    <i class="ti tabler-chevron-right icon-base"></i>
                </a>
            </div>
            <!-- / Menu -->
