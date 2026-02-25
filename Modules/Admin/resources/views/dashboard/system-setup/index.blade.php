@extends('admin::components.layouts.master')
@section('title', 'System Setup | RoomGate Admin')
@section('page-title', 'System Setup')

@section('content')
@php
  $meta = $settings->meta ?? [];
  $twoFactorRoles = (array) data_get($meta, 'two_factor.roles', ['platform_admin', 'admin', 'owner']);
  $apiRoles = (array) data_get($meta, 'api.allowed_roles', []);
  $enabledLocales = (array) data_get($meta, 'language.enabled_locales', ['en']);
  $sections = [
    'general' => 'General',
    'two-factor' => 'Two Factor',
    'email' => 'Email',
    'sms' => 'SMS',
    'api' => 'API Permission',
    'notifications' => 'Notifications',
    'language' => 'Language',
    'currency' => 'Currency',
    'utility' => 'Utility',
    'cron' => 'Cron',
    'backup' => 'Backup',
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <ul class="nav nav-pills flex-wrap gap-2">
        @foreach($sections as $key => $label)
          <li class="nav-item">
            <a class="nav-link {{ $section === $key ? 'active' : '' }}" href="{{ route('admin.system-setup.section', ['section' => $key]) }}">{{ $label }}</a>
          </li>
        @endforeach
      </ul>
    </div>
  </div>

  @if($section === 'general')
    <div class="card">
      <h5 class="card-header">General Settings</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.general.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-4"><label class="form-label">App Name</label><input class="form-control" name="app_name" value="{{ old('app_name', $settings->app_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Short Name</label><input class="form-control" name="app_short_name" value="{{ old('app_short_name', $settings->app_short_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Company Name</label><input class="form-control" name="company_name" value="{{ old('company_name', $settings->company_name) }}"></div>
          <div class="col-md-6"><label class="form-label">Default Timezone</label><input class="form-control" name="default_timezone" value="{{ old('default_timezone', data_get($meta, 'general.default_timezone', 'UTC')) }}"></div>
          <div class="col-md-6"><label class="form-label">Default Currency (ISO)</label><input class="form-control" name="default_currency" value="{{ old('default_currency', data_get($meta, 'general.default_currency', 'USD')) }}"></div>
          <div class="col-12"><button class="btn btn-primary">Save General</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'two-factor')
    <div class="card">
      <h5 class="card-header">Two Factor Setting</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.two-factor.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1" {{ data_get($meta, 'two_factor.enabled') ? 'checked' : '' }}><label class="form-check-label">Enable 2FA</label></div></div>
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="via_email" value="1" {{ data_get($meta, 'two_factor.via_email', true) ? 'checked' : '' }}><label class="form-check-label">Via Email</label></div></div>
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="via_sms" value="1" {{ data_get($meta, 'two_factor.via_sms') ? 'checked' : '' }}><label class="form-check-label">Via SMS</label></div></div>
          <div class="col-md-3"><label class="form-label">TTL (minutes)</label><input class="form-control" type="number" min="1" max="60" name="ttl_minutes" value="{{ old('ttl_minutes', data_get($meta, 'two_factor.ttl_minutes', 10)) }}"></div>
          <div class="col-12">
            <label class="form-label d-block">Apply to Roles</label>
            @foreach($roles as $role)
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" {{ in_array($role->name, $twoFactorRoles, true) ? 'checked' : '' }}><label class="form-check-label">{{ $role->name }}</label></div>
            @endforeach
          </div>
          <div class="col-12"><button class="btn btn-primary">Save Two Factor</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'email')
    <div class="card">
      <h5 class="card-header">Email Settings</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.email.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1" {{ data_get($meta, 'email.enabled') ? 'checked' : '' }}><label class="form-check-label">Enable</label></div></div>
          <div class="col-md-3"><label class="form-label">Driver</label><select class="form-select" name="driver">@foreach(['smtp','log','sendmail'] as $driver)<option value="{{ $driver }}" {{ data_get($meta, 'email.driver', 'smtp') === $driver ? 'selected' : '' }}>{{ strtoupper($driver) }}</option>@endforeach</select></div>
          <div class="col-md-3"><label class="form-label">From Name</label><input class="form-control" name="from_name" value="{{ old('from_name', data_get($meta, 'email.from_name')) }}"></div>
          <div class="col-md-3"><label class="form-label">From Address</label><input class="form-control" name="from_address" value="{{ old('from_address', data_get($meta, 'email.from_address')) }}"></div>
          <div class="col-md-3"><label class="form-label">Host</label><input class="form-control" name="host" value="{{ old('host', data_get($meta, 'email.host')) }}"></div>
          <div class="col-md-2"><label class="form-label">Port</label><input class="form-control" type="number" name="port" value="{{ old('port', data_get($meta, 'email.port')) }}"></div>
          <div class="col-md-2"><label class="form-label">Encryption</label><select class="form-select" name="encryption"><option value="null">None</option><option value="tls" {{ data_get($meta, 'email.encryption') === 'tls' ? 'selected' : '' }}>TLS</option><option value="ssl" {{ data_get($meta, 'email.encryption') === 'ssl' ? 'selected' : '' }}>SSL</option></select></div>
          <div class="col-md-2"><label class="form-label">Username</label><input class="form-control" name="username" value="{{ old('username', data_get($meta, 'email.username')) }}"></div>
          <div class="col-md-3"><label class="form-label">Password</label><input class="form-control" name="password" value=""></div>
          <div class="col-12"><button class="btn btn-primary">Save Email</button></div>
        </form>
        <hr>
        <form method="POST" action="{{ route('admin.system-setup.email.test') }}" class="row g-3">
          @csrf
          <div class="col-md-6"><input class="form-control" name="email" placeholder="test@example.com"></div>
          <div class="col-md-3"><button class="btn btn-label-primary">Send Test Email</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'sms')
    <div class="card">
      <h5 class="card-header">SMS Settings</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.sms.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-2"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1" {{ data_get($meta, 'sms.enabled') ? 'checked' : '' }}><label class="form-check-label">Enable</label></div></div>
          <div class="col-md-2"><label class="form-label">Provider</label><select class="form-select" name="provider"><option value="none" {{ data_get($meta, 'sms.provider', 'none') === 'none' ? 'selected' : '' }}>None</option><option value="twilio" {{ data_get($meta, 'sms.provider') === 'twilio' ? 'selected' : '' }}>Twilio</option><option value="webhook" {{ data_get($meta, 'sms.provider') === 'webhook' ? 'selected' : '' }}>Webhook</option></select></div>
          <div class="col-md-2"><label class="form-label">From Number</label><input class="form-control" name="from_number" value="{{ old('from_number', data_get($meta, 'sms.from_number')) }}"></div>
          <div class="col-md-3"><label class="form-label">API Key/SID</label><input class="form-control" name="api_key" value="{{ old('api_key', data_get($meta, 'sms.api_key')) }}"></div>
          <div class="col-md-3"><label class="form-label">API Secret/Token</label><input class="form-control" name="api_secret" value=""></div>
          <div class="col-md-6"><label class="form-label">Webhook URL</label><input class="form-control" name="webhook_url" value="{{ old('webhook_url', data_get($meta, 'sms.webhook_url')) }}"></div>
          <div class="col-12"><button class="btn btn-primary">Save SMS</button></div>
        </form>
        <hr>
        <form method="POST" action="{{ route('admin.system-setup.sms.test') }}" class="row g-3">
          @csrf
          <div class="col-md-3"><input class="form-control" name="to" placeholder="+85512345678"></div>
          <div class="col-md-6"><input class="form-control" name="message" value="RoomGate test SMS"></div>
          <div class="col-md-3"><button class="btn btn-label-primary">Send Test SMS</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'api')
    <div class="card">
      <h5 class="card-header">API Permission</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.api.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1" {{ data_get($meta, 'api.enabled', true) ? 'checked' : '' }}><label class="form-check-label">Enable API</label></div></div>
          <div class="col-12">
            <label class="form-label d-block">Allowed Roles for authenticated API</label>
            @foreach($roles as $role)
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="allowed_roles[]" value="{{ $role->name }}" {{ in_array($role->name, $apiRoles, true) ? 'checked' : '' }}><label class="form-check-label">{{ $role->name }}</label></div>
            @endforeach
          </div>
          <div class="col-12"><button class="btn btn-primary">Save API Permission</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'notifications')
    <div class="card">
      <h5 class="card-header">Notification Setting</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.notifications.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-3"><label class="form-label">Invoice Due Soon Days</label><input class="form-control" type="number" min="1" max="30" name="invoice_due_soon_days" value="{{ old('invoice_due_soon_days', data_get($meta, 'notifications.invoice_due_soon_days', 3)) }}"></div>
          <div class="col-md-3"><label class="form-label">Trial Ending Days</label><input class="form-control" type="number" min="1" max="30" name="trial_ending_days" value="{{ old('trial_ending_days', data_get($meta, 'notifications.trial_ending_days', 3)) }}"></div>
          <div class="col-md-3"><label class="form-label">Quiet Hours Start</label><input class="form-control" type="time" name="quiet_hours_start" value="{{ old('quiet_hours_start', data_get($meta, 'notifications.quiet_hours_start')) }}"></div>
          <div class="col-md-3"><label class="form-label">Quiet Hours End</label><input class="form-control" type="time" name="quiet_hours_end" value="{{ old('quiet_hours_end', data_get($meta, 'notifications.quiet_hours_end')) }}"></div>
          <div class="col-12"><button class="btn btn-primary">Save Notifications</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'language')
    <div class="card">
      <h5 class="card-header">Language Settings</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.language.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-3"><label class="form-label">Default Locale</label><input class="form-control" name="default_locale" value="{{ old('default_locale', data_get($meta, 'language.default_locale', 'en')) }}"></div>
          <div class="col-md-9">
            <label class="form-label d-block">Enabled Locales</label>
            @foreach(['en','km','th','vi','zh','fr'] as $locale)
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="enabled_locales[]" value="{{ $locale }}" {{ in_array($locale, $enabledLocales, true) ? 'checked' : '' }}><label class="form-check-label">{{ $locale }}</label></div>
            @endforeach
          </div>
          <div class="col-12"><button class="btn btn-primary">Save Language Settings</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'currency')
    <div class="card">
      <h5 class="card-header">Manage Currency</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.system-setup.currencies.store') }}" class="row g-3 mb-4">
          @csrf
          <div class="col-md-2"><input class="form-control" name="code" placeholder="USD"></div>
          <div class="col-md-2"><input class="form-control" name="name" placeholder="US Dollar"></div>
          <div class="col-md-1"><input class="form-control" name="symbol" placeholder="$"></div>
          <div class="col-md-2"><input class="form-control" type="number" name="decimal_places" value="2" min="0" max="6"></div>
          <div class="col-md-2"><select class="form-select" name="symbol_position"><option value="prefix">Prefix</option><option value="suffix">Suffix</option></select></div>
          <div class="col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active</label></div></div>
          <div class="col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_default" value="1"><label class="form-check-label">Default</label></div></div>
          <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Code</th><th>Name</th><th>Symbol</th><th>Format</th><th>Status</th><th>Default</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($currencies as $currency)
              <tr>
                <td>{{ $currency->code }}</td>
                <td>{{ $currency->name }}</td>
                <td>{{ $currency->symbol }}</td>
                <td>{{ $currency->symbol_position }} / {{ $currency->decimal_places }}</td>
                <td>{{ $currency->is_active ? 'Active' : 'Inactive' }}</td>
                <td>{{ $currency->is_default ? 'Yes' : 'No' }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.system-setup.currencies.delete', $currency) }}" style="display:inline-block" data-confirm="Delete this currency?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-label-danger" {{ $currency->is_default ? 'disabled' : '' }}>Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-body-secondary">No currencies configured.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @elseif($section === 'utility')
    <div class="card">
      <h5 class="card-header">Utility Settings</h5>
      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <form method="POST" action="{{ route('admin.system-setup.utility.clear-cache') }}">
              @csrf
              <button type="submit" class="btn btn-info w-100">Clear Cache</button>
            </form>
          </div>
          <div class="col-md-3">
            <form method="POST" action="{{ route('admin.system-setup.utility.clear-log') }}">
              @csrf
              <button type="submit" class="btn btn-secondary w-100">Clear Log</button>
            </form>
          </div>
          <div class="col-md-3">
            <form method="POST" action="{{ route('admin.system-setup.utility.toggle-debug') }}">
              @csrf
              @php($debugEnabled = (bool) data_get($meta, 'utility.app_debug', false))
              <input type="hidden" name="enabled" value="{{ $debugEnabled ? 0 : 1 }}">
              <button type="submit" class="btn {{ $debugEnabled ? 'btn-danger' : 'btn-success' }} w-100">
                {{ $debugEnabled ? 'Disable App Debug' : 'Enable App Debug' }}
              </button>
            </form>
          </div>
          <div class="col-md-3">
            <form method="POST" action="{{ route('admin.system-setup.utility.toggle-force-https') }}">
              @csrf
              @php($httpsEnabled = (bool) data_get($meta, 'utility.force_https', false))
              <input type="hidden" name="enabled" value="{{ $httpsEnabled ? 0 : 1 }}">
              <button type="submit" class="btn {{ $httpsEnabled ? 'btn-danger' : 'btn-info' }} w-100">
                {{ $httpsEnabled ? 'Disable Force HTTPS' : 'Enable Force HTTPS' }}
              </button>
            </form>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="mb-3">Maintenance Mode Setting</h6>
        <form method="POST" action="{{ route('admin.system-setup.utility.update') }}" class="row g-3" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="col-md-2">
            <div class="form-check form-switch mt-4">
              <input class="form-check-input" type="checkbox" name="maintenance_enabled" value="1" {{ data_get($meta, 'utility.maintenance.enabled', false) ? 'checked' : '' }}>
              <label class="form-check-label">Maintenance Mode</label>
            </div>
          </div>
          <div class="col-md-5"><label class="form-label">Title</label><input class="form-control" name="maintenance_title" value="{{ old('maintenance_title', data_get($meta, 'utility.maintenance.title', 'We will be back soon!')) }}"></div>
          <div class="col-md-5"><label class="form-label">Sub Title</label><input class="form-control" name="maintenance_subtitle" value="{{ old('maintenance_subtitle', data_get($meta, 'utility.maintenance.subtitle', 'Sorry for the inconvenience but we are performing some maintenance at the moment.')) }}"></div>
          <div class="col-12">
            <label class="form-label d-block">Applicable for</label>
            @php($mRoles = (array) data_get($meta, 'utility.maintenance.applicable_roles', []))
            @foreach($roles as $role)
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="maintenance_roles[]" value="{{ $role->name }}" {{ in_array($role->name, $mRoles, true) ? 'checked' : '' }}>
                <label class="form-check-label">{{ ucfirst(str_replace('_',' ', $role->name)) }}</label>
              </div>
            @endforeach
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="maintenance_frontend" value="1" {{ data_get($meta, 'utility.maintenance.frontend', false) ? 'checked' : '' }}>
              <label class="form-check-label">Frontend/Website</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Upload Image</label>
            <input class="form-control" type="file" name="maintenance_image" accept=".jpg,.jpeg,.png,.webp">
          </div>
          @if(data_get($meta, 'utility.maintenance.image_path'))
            <div class="col-md-6">
              <img
                src="{{ asset(data_get($meta, 'utility.maintenance.image_path')) }}"
                alt="Maintenance image preview"
                class="img-fluid rounded border"
                style="max-height: 160px;">
            </div>
          @endif

          <hr class="my-4">
          <h6 class="mb-2">Utility Billing Defaults</h6>
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="auto_generate_with_invoice" value="1" {{ data_get($meta, 'utility.auto_generate_with_invoice', true) ? 'checked' : '' }}><label class="form-check-label">Auto-generate utility items with invoices</label></div></div>
          <div class="col-md-3"><label class="form-label">Default Utility Tax (%)</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="default_tax_percent" value="{{ old('default_tax_percent', data_get($meta, 'utility.default_tax_percent', 0)) }}"></div>
          <div class="col-md-3"><label class="form-label">Utility Bill Due Days</label><input class="form-control" type="number" min="1" max="60" name="billing_due_days" value="{{ old('billing_due_days', data_get($meta, 'utility.billing_due_days', 7)) }}"></div>
          <div class="col-md-3"><label class="form-label">Default Utility Currency</label><input class="form-control" name="default_unit_currency" value="{{ old('default_unit_currency', data_get($meta, 'utility.default_unit_currency', 'USD')) }}"></div>
          <div class="col-12"><button class="btn btn-primary">Save Utility Settings</button></div>
        </form>
      </div>
    </div>
  @elseif($section === 'cron')
    <div class="card">
      <h5 class="card-header">Cron Job Visibility</h5>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            @php($state = data_get($cronHealth ?? [], 'state'))
            <span class="badge {{ $state === 'running' ? 'bg-label-success' : ($state === 'stale' ? 'bg-label-warning' : 'bg-label-secondary') }}">
              Cron Status: {{ data_get($cronHealth ?? [], 'label', 'Unknown') }}
            </span>
            @if(data_get($cronHealth ?? [], 'last_heartbeat_at'))
              <small class="text-body-secondary ms-2">Last heartbeat: {{ data_get($cronHealth, 'last_heartbeat_at') }}</small>
            @endif
          </div>
          <form method="POST" action="{{ route('admin.system-setup.cron.run') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Run Cron Now</button>
          </form>
        </div>
        <pre class="bg-light p-3 rounded" style="max-height:320px; overflow:auto;">{{ $cronList }}</pre>
      </div>
    </div>
  @elseif($section === 'backup')
    <div class="card">
      <h5 class="card-header">Backup</h5>
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="alert alert-info mb-2">Latest DB Backup: {{ $latestDbBackup['name'] ?? 'None' }} @if($latestDbBackup) ({{ $latestDbBackup['modified_at'] }}) @endif</div>
            @if($latestDbBackup)
              <a href="{{ route('admin.system-setup.backups.download', ['target' => 'db']) }}" class="btn btn-sm btn-label-primary">Download Latest DB Backup</a>
            @endif
          </div>
          <div class="col-md-6">
            <div class="alert alert-info mb-2">Latest Upload Backup: {{ $latestUploadBackup['name'] ?? 'None' }} @if($latestUploadBackup) ({{ $latestUploadBackup['modified_at'] }}) @endif</div>
            @if($latestUploadBackup)
              <a href="{{ route('admin.system-setup.backups.download', ['target' => 'uploads']) }}" class="btn btn-sm btn-label-primary">Download Latest Upload Backup</a>
            @endif
          </div>
        </div>
        <form method="POST" action="{{ route('admin.system-setup.backups.run') }}" class="d-flex gap-2">
          @csrf
          <button type="submit" name="target" value="all" class="btn btn-primary">Run Full Backup</button>
          <button type="submit" name="target" value="db" class="btn btn-label-secondary">Run DB Backup</button>
          <button type="submit" name="target" value="uploads" class="btn btn-label-secondary">Run Upload Backup</button>
        </form>
      </div>
    </div>
  @endif
</div>
@endsection
