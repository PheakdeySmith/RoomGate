<div class="nav-align-top">
  <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-6 row-gap-2" data-ajax-tabs="user-view">
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('admin.tenants.account') ? 'active' : '' }}" data-ajax-link="user-view"
        href="{{ route('admin.tenants.account', $tenant) }}">
        <i class="icon-base ti tabler-user-check icon-sm me-1_5"></i>Account
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('admin.tenants.security') ? 'active' : '' }}" data-ajax-link="user-view"
        href="{{ route('admin.tenants.security', $tenant) }}">
        <i class="icon-base ti tabler-lock icon-sm me-1_5"></i>Security
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('admin.tenants.billing') ? 'active' : '' }}" data-ajax-link="user-view"
        href="{{ route('admin.tenants.billing', $tenant) }}">
        <i class="icon-base ti tabler-bookmark icon-sm me-1_5"></i>Billing & Plans
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('admin.tenants.notifications') ? 'active' : '' }}" data-ajax-link="user-view"
        href="{{ route('admin.tenants.notifications', $tenant) }}">
        <i class="icon-base ti tabler-bell icon-sm me-1_5"></i>Notifications
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('admin.tenants.connections') ? 'active' : '' }}" data-ajax-link="user-view"
        href="{{ route('admin.tenants.connections', $tenant) }}">
        <i class="icon-base ti tabler-link icon-sm me-1_5"></i>Connections
      </a>
    </li>
  </ul>
</div>
