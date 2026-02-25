<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\BusinessSetting;
use App\Models\Currency;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Support\EnforcesOptionalPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AdminSystemSetupController extends Controller
{
    use EnforcesOptionalPermission;

    private const SECTIONS = [
        'general',
        'two-factor',
        'email',
        'sms',
        'api',
        'notifications',
        'language',
        'currency',
        'utility',
        'cron',
        'backup',
    ];

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.system-setup.section', ['section' => 'general']);
    }

    public function section(Request $request, string $section)
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');

        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        if (!Currency::query()->exists()) {
            Currency::query()->create([
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'symbol_position' => 'prefix',
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $settings = BusinessSetting::current();
        $currencies = Currency::query()->orderBy('code')->get();
        $roles = Role::query()->orderBy('name')->get(['name']);

        $cronList = $this->scheduleListOutput();
        $cronHealth = $this->cronHealth($settings);
        $latestDbBackup = $this->latestBackupFile(storage_path('backups/db'));
        $latestUploadBackup = $this->latestBackupFile(storage_path('backups/uploads'));

        return view('admin::dashboard.system-setup.index', compact(
            'section',
            'settings',
            'currencies',
            'roles',
            'cronList',
            'cronHealth',
            'latestDbBackup',
            'latestUploadBackup'
        ));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_short_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'default_timezone' => ['nullable', 'string', 'max:64'],
            'default_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $settings = BusinessSetting::current();
        $settings->fill([
            'app_name' => $validated['app_name'] ?? $settings->app_name,
            'app_short_name' => $validated['app_short_name'] ?? $settings->app_short_name,
            'company_name' => $validated['company_name'] ?? $settings->company_name,
        ]);
        $settings->putMetaValue('general.default_timezone', $validated['default_timezone'] ?? 'UTC');
        $settings->putMetaValue('general.default_currency', strtoupper((string) ($validated['default_currency'] ?? 'USD')));
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'General settings updated.');
    }

    public function updateTwoFactor(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'via_email' => ['nullable', 'boolean'],
            'via_sms' => ['nullable', 'boolean'],
            'ttl_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('two_factor.enabled', $request->boolean('enabled'));
        $settings->putMetaValue('two_factor.via_email', $request->boolean('via_email', true));
        $settings->putMetaValue('two_factor.via_sms', $request->boolean('via_sms', false));
        $settings->putMetaValue('two_factor.ttl_minutes', (int) $validated['ttl_minutes']);
        $settings->putMetaValue('two_factor.roles', array_values($validated['roles'] ?? ['platform_admin', 'admin', 'owner']));
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'Two-factor settings updated.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'driver' => ['required', 'in:smtp,log,sendmail'],
            'from_name' => ['nullable', 'string', 'max:191'],
            'from_address' => ['nullable', 'email', 'max:191'],
            'host' => ['nullable', 'string', 'max:191'],
            'port' => ['nullable', 'integer', 'min:1'],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
            'encryption' => ['nullable', 'in:tls,ssl,null'],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('email.enabled', $request->boolean('enabled'));
        $settings->putMetaValue('email.driver', $validated['driver']);
        $settings->putMetaValue('email.from_name', $validated['from_name'] ?? null);
        $settings->putMetaValue('email.from_address', $validated['from_address'] ?? null);
        $settings->putMetaValue('email.host', $validated['host'] ?? null);
        $settings->putMetaValue('email.port', $validated['port'] ?? null);
        $settings->putMetaValue('email.username', $validated['username'] ?? null);
        if (array_key_exists('password', $validated) && $validated['password'] !== null && $validated['password'] !== '') {
            $settings->putMetaValue('email.password', $validated['password']);
        }
        $settings->putMetaValue('email.encryption', $validated['encryption'] ?? null);
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'Email settings updated.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Mail::to($request->input('email'))->send(new OtpCodeMail('123456', 'login_2fa'));

        return back()->with('status', 'Test email sent.');
    }

    public function updateSms(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['required', 'in:twilio,webhook,none'],
            'from_number' => ['nullable', 'string', 'max:40'],
            'api_key' => ['nullable', 'string', 'max:191'],
            'api_secret' => ['nullable', 'string', 'max:191'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('sms.enabled', $request->boolean('enabled'));
        $settings->putMetaValue('sms.provider', $validated['provider']);
        $settings->putMetaValue('sms.from_number', $validated['from_number'] ?? null);
        $settings->putMetaValue('sms.api_key', $validated['api_key'] ?? null);
        $settings->putMetaValue('sms.api_secret', $validated['api_secret'] ?? null);
        $settings->putMetaValue('sms.webhook_url', $validated['webhook_url'] ?? null);
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'SMS settings updated.');
    }

    public function sendTestSms(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $settings = BusinessSetting::current();
        if (!(bool) $settings->metaValue('sms.enabled', false)) {
            return back()->withErrors(['sms' => 'SMS is disabled.']);
        }

        $provider = (string) $settings->metaValue('sms.provider', 'none');
        if ($provider === 'webhook') {
            $url = (string) $settings->metaValue('sms.webhook_url', '');
            if ($url === '') {
                return back()->withErrors(['sms' => 'Webhook URL is required for webhook provider.']);
            }
            Http::timeout(8)->post($url, [
                'to' => $validated['to'],
                'message' => $validated['message'],
                'from' => $settings->metaValue('sms.from_number'),
            ]);
            return back()->with('status', 'Test SMS request sent via webhook provider.');
        }

        if ($provider === 'twilio') {
            $sid = (string) $settings->metaValue('sms.api_key', '');
            $token = (string) $settings->metaValue('sms.api_secret', '');
            $from = (string) $settings->metaValue('sms.from_number', '');
            if ($sid === '' || $token === '' || $from === '') {
                return back()->withErrors(['sms' => 'Twilio SID, token and from number are required.']);
            }

            Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $validated['to'],
                    'Body' => $validated['message'],
                ]);

            return back()->with('status', 'Test SMS request sent via Twilio.');
        }

        return back()->withErrors(['sms' => 'No supported SMS provider configured.']);
    }

    public function updateApi(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'allowed_roles' => ['array'],
            'allowed_roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('api.enabled', $request->boolean('enabled', true));
        $settings->putMetaValue('api.allowed_roles', array_values($validated['allowed_roles'] ?? []));
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'API permission settings updated.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'invoice_due_soon_days' => ['required', 'integer', 'min:1', 'max:30'],
            'trial_ending_days' => ['required', 'integer', 'min:1', 'max:30'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('notifications.invoice_due_soon_days', (int) $validated['invoice_due_soon_days']);
        $settings->putMetaValue('notifications.trial_ending_days', (int) $validated['trial_ending_days']);
        $settings->putMetaValue('notifications.quiet_hours_start', $validated['quiet_hours_start'] ?? null);
        $settings->putMetaValue('notifications.quiet_hours_end', $validated['quiet_hours_end'] ?? null);
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'Notification settings updated.');
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'default_locale' => ['required', 'string', 'max:10'],
            'enabled_locales' => ['array'],
            'enabled_locales.*' => ['string', 'max:10'],
        ]);

        $enabled = array_values(array_unique($validated['enabled_locales'] ?? []));
        if (!in_array($validated['default_locale'], $enabled, true)) {
            $enabled[] = $validated['default_locale'];
        }

        $settings = BusinessSetting::current();
        $settings->putMetaValue('language.default_locale', $validated['default_locale']);
        $settings->putMetaValue('language.enabled_locales', $enabled);
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'Language settings updated.');
    }

    public function updateUtility(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'auto_generate_with_invoice' => ['nullable', 'boolean'],
            'default_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_due_days' => ['required', 'integer', 'min:1', 'max:60'],
            'default_unit_currency' => ['nullable', 'string', 'size:3'],
            'maintenance_enabled' => ['nullable', 'boolean'],
            'maintenance_title' => ['nullable', 'string', 'max:191'],
            'maintenance_subtitle' => ['nullable', 'string', 'max:500'],
            'maintenance_roles' => ['array'],
            'maintenance_roles.*' => ['string', Rule::exists('roles', 'name')],
            'maintenance_frontend' => ['nullable', 'boolean'],
            'maintenance_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $settings = BusinessSetting::current();
        $settings->putMetaValue('utility.auto_generate_with_invoice', $request->boolean('auto_generate_with_invoice', true));
        $settings->putMetaValue('utility.default_tax_percent', (float) ($validated['default_tax_percent'] ?? 0));
        $settings->putMetaValue('utility.billing_due_days', (int) $validated['billing_due_days']);
        $settings->putMetaValue('utility.default_unit_currency', strtoupper((string) ($validated['default_unit_currency'] ?? 'USD')));
        $settings->putMetaValue('utility.maintenance.enabled', $request->boolean('maintenance_enabled'));
        $settings->putMetaValue('utility.maintenance.title', (string) ($validated['maintenance_title'] ?? 'We will be back soon!'));
        $settings->putMetaValue('utility.maintenance.subtitle', (string) ($validated['maintenance_subtitle'] ?? 'Sorry for the inconvenience but we are performing some maintenance at the moment.'));
        $settings->putMetaValue('utility.maintenance.applicable_roles', array_values($validated['maintenance_roles'] ?? []));
        $settings->putMetaValue('utility.maintenance.frontend', $request->boolean('maintenance_frontend'));

        if ($request->hasFile('maintenance_image')) {
            $file = $request->file('maintenance_image');
            $targetDir = public_path('uploads/images');
            File::ensureDirectoryExists($targetDir);
            $name = 'maintenance_' . now()->format('Ymd_His') . '.' . ($file?->getClientOriginalExtension() ?: 'png');
            $file->move($targetDir, $name);
            $path = 'uploads/images/' . $name;
            $settings->putMetaValue('utility.maintenance.image_path', $path);

            app(AuditLogger::class)->log(
                'uploaded',
                'maintenance_image',
                $name,
                null,
                ['path' => $path],
                $request
            );
        }
        $settings->save();

        Cache::forget('business_settings:current');

        return back()->with('status', 'Utility settings updated.');
    }

    public function clearCache(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        Artisan::call('optimize:clear');
        return back()->with('status', 'Cache cleared successfully.');
    }

    public function clearLog(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $logFile = storage_path('logs/laravel.log');
        if (is_file($logFile)) {
            file_put_contents($logFile, '');
        }
        return back()->with('status', 'Application log cleared.');
    }

    public function toggleDebug(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $request->validate(['enabled' => ['required', 'boolean']]);
        $settings = BusinessSetting::current();
        $settings->putMetaValue('utility.app_debug', $request->boolean('enabled'));
        $settings->save();
        Cache::forget('business_settings:current');

        return back()->with('status', $request->boolean('enabled') ? 'App debug enabled.' : 'App debug disabled.');
    }

    public function toggleForceHttps(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $request->validate(['enabled' => ['required', 'boolean']]);

        $host = (string) $request->getHost();
        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test');
        if ($request->boolean('enabled') && $isLocalHost) {
            return back()->withErrors(['https' => 'Force HTTPS cannot be enabled on localhost/test domains.']);
        }

        $settings = BusinessSetting::current();
        $settings->putMetaValue('utility.force_https', $request->boolean('enabled'));
        $settings->save();
        Cache::forget('business_settings:current');

        return back()->with('status', $request->boolean('enabled') ? 'Force HTTPS enabled.' : 'Force HTTPS disabled.');
    }

    public function storeCurrency(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')],
            'name' => ['required', 'string', 'max:80'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:6'],
            'symbol_position' => ['required', 'in:prefix,suffix'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            if ($request->boolean('is_default')) {
                Currency::query()->update(['is_default' => false]);
            }

            Currency::create([
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'symbol' => $validated['symbol'] ?? null,
                'decimal_places' => (int) $validated['decimal_places'],
                'symbol_position' => $validated['symbol_position'],
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        return back()->with('status', 'Currency added.');
    }

    public function updateCurrency(Request $request, Currency $currency): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:6'],
            'symbol_position' => ['required', 'in:prefix,suffix'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $currency, $validated) {
            if ($request->boolean('is_default')) {
                Currency::query()->update(['is_default' => false]);
            }

            $currency->update([
                'name' => $validated['name'],
                'symbol' => $validated['symbol'] ?? null,
                'decimal_places' => (int) $validated['decimal_places'],
                'symbol_position' => $validated['symbol_position'],
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        return back()->with('status', 'Currency updated.');
    }

    public function deleteCurrency(Currency $currency): RedirectResponse
    {
        $this->enforceOptionalPermission(request(), 'system_setup.manage');
        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'Default currency cannot be deleted.']);
        }

        $currency->delete();

        return back()->with('status', 'Currency removed.');
    }

    public function runBackup(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');
        $target = $request->input('target', 'all');
        if (!in_array($target, ['all', 'db', 'uploads'], true)) {
            return back()->withErrors(['backup' => 'Invalid backup target.']);
        }

        $command = match ($target) {
            'db' => 'roomgate:backup-db',
            'uploads' => 'roomgate:backup-uploads',
            default => 'roomgate:backup-all',
        };

        Artisan::call($command);
        $output = trim(Artisan::output());

        return back()->with('status', 'Backup command executed: ' . $command . ($output ? (' | ' . $output) : ''));
    }

    public function downloadLatestBackup(Request $request, string $target)
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');

        if (!in_array($target, ['db', 'uploads'], true)) {
            abort(404);
        }

        $directory = $target === 'db' ? storage_path('backups/db') : storage_path('backups/uploads');
        $latest = $this->latestBackupFile($directory);

        if (!$latest) {
            return back()->withErrors(['backup' => 'No backup file available to download.']);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $latest['name'];
        if (!is_file($path)) {
            return back()->withErrors(['backup' => 'Backup file not found.']);
        }

        app(AuditLogger::class)->log(
            'downloaded',
            'backup_file',
            $latest['name'],
            null,
            [
                'target' => $target,
                'name' => $latest['name'],
                'size' => $latest['size'] ?? null,
            ],
            $request
        );

        return response()->download($path, $latest['name']);
    }

    public function runCron(Request $request): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'system_setup.manage');

        // Record an immediate heartbeat for manual run visibility.
        $settings = BusinessSetting::current();
        $settings->putMetaValue('cron.last_heartbeat_at', now()->toDateTimeString());
        $settings->save();
        Cache::forget('business_settings:current');

        Artisan::call('schedule:run');
        $output = trim(Artisan::output());
        if ($output !== '') {
            Log::info('Manual cron run output', ['output' => $output]);
        }

        $message = str_contains($output, 'No scheduled commands are ready to run.')
            ? 'Cron checked. No due tasks at this minute.'
            : 'Cron executed successfully.';

        return back()->with('status', $message);
    }

    private function scheduleListOutput(): string
    {
        Artisan::call('schedule:list');
        return trim(Artisan::output());
    }

    private function latestBackupFile(string $directory): ?array
    {
        if (!is_dir($directory)) {
            return null;
        }

        $files = collect(glob($directory . DIRECTORY_SEPARATOR . '*'))
            ->filter(fn ($path) => is_file($path))
            ->sortDesc()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $file = (string) $files->first();
        return [
            'name' => basename($file),
            'size' => filesize($file) ?: 0,
            'modified_at' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
        ];
    }

    private function cronHealth(BusinessSetting $settings): array
    {
        $value = $settings->metaValue('cron.last_heartbeat_at');
        if (!is_string($value) || trim($value) === '') {
            return [
                'state' => 'unknown',
                'label' => 'Unknown',
                'last_heartbeat_at' => null,
                'age_seconds' => null,
            ];
        }

        try {
            $heartbeat = Carbon::parse($value);
        } catch (\Throwable) {
            return [
                'state' => 'unknown',
                'label' => 'Unknown',
                'last_heartbeat_at' => null,
                'age_seconds' => null,
            ];
        }

        $ageSeconds = $heartbeat->diffInSeconds(now());
        $isRunning = $ageSeconds <= 120;

        return [
            'state' => $isRunning ? 'running' : 'stale',
            'label' => $isRunning ? 'Running' : 'Stale',
            'last_heartbeat_at' => $heartbeat->format('Y-m-d H:i:s'),
            'age_seconds' => $ageSeconds,
        ];
    }
}
