<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Amenity;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\UtilityBill;
use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Models\UtilityRate;
use App\Models\MaintenanceRequest;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Google\Provider as GoogleProvider;
use SocialiteProviders\Telegram\Provider as TelegramProvider;
use Modules\Core\App\Services\CurrentTenant;
use App\Policies\TenantOwnedPolicy;
use App\Services\AuditLogger;
use App\Events\RentInvoiceCreated;
use App\Events\RentInvoiceDueSoon;
use App\Events\RentInvoiceOverdue;
use App\Events\ContractCreated;
use App\Events\ContractStatusChanged;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionTrialEndingSoon;
use App\Events\SubscriptionRenewalFailed;
use App\Events\SubscriptionRenewalSucceeded;
use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionPaymentReceived;
use App\Events\SubscriptionPaymentFailed;
use App\Events\SubscriptionPaymentRefunded;
use App\Events\TenantMemberInvited;
use App\Events\TenantMemberAccepted;
use App\Events\TenantMemberDisabled;
use App\Listeners\SendRentInvoiceCreatedNotifications;
use App\Listeners\SendRentInvoiceDueSoonNotifications;
use App\Listeners\SendRentInvoiceOverdueNotifications;
use App\Listeners\SendContractCreatedNotifications;
use App\Listeners\SendContractStatusChangedNotifications;
use App\Listeners\SendSubscriptionCreatedNotifications;
use App\Listeners\SendSubscriptionTrialEndingNotifications;
use App\Listeners\SendSubscriptionRenewalFailedNotifications;
use App\Listeners\SendSubscriptionRenewalSucceededNotifications;
use App\Listeners\SendSubscriptionCancelledNotifications;
use App\Listeners\SendSubscriptionExpiredNotifications;
use App\Listeners\SendSubscriptionPaymentReceivedNotifications;
use App\Listeners\SendSubscriptionPaymentFailedNotifications;
use App\Listeners\SendSubscriptionPaymentRefundedNotifications;
use App\Listeners\SendTenantMemberInvitedNotifications;
use App\Listeners\SendTenantMemberAcceptedNotifications;
use App\Listeners\SendTenantMemberDisabledNotifications;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $meta = [];
        if (Schema::hasTable('business_settings')) {
            try {
                $settings = BusinessSetting::current();
                $meta = $settings->meta ?? [];
            } catch (\Throwable) {
                $meta = [];
            }
        }

        $defaultLocale = (string) data_get($meta, 'language.default_locale', config('app.locale', 'en'));
        if ($defaultLocale !== '') {
            Config::set('app.locale', $defaultLocale);
            app()->setLocale($defaultLocale);
        }

        $defaultTimezone = (string) data_get($meta, 'general.default_timezone', config('app.timezone', 'UTC'));
        if ($defaultTimezone !== '') {
            Config::set('app.timezone', $defaultTimezone);
            date_default_timezone_set($defaultTimezone);
        }

        Config::set('services.notifications.invoice_due_soon_days', (int) data_get($meta, 'notifications.invoice_due_soon_days', config('services.notifications.invoice_due_soon_days', 3)));
        Config::set('services.notifications.trial_ending_days', (int) data_get($meta, 'notifications.trial_ending_days', config('services.notifications.trial_ending_days', 3)));
        Config::set('app.debug', (bool) data_get($meta, 'utility.app_debug', config('app.debug', false)));

        if ((bool) data_get($meta, 'email.enabled', false)) {
            $driver = (string) data_get($meta, 'email.driver', 'smtp');
            $mailConfig = [
                'transport' => $driver === 'sendmail' ? 'sendmail' : ($driver === 'log' ? 'log' : 'smtp'),
                'host' => data_get($meta, 'email.host', config('mail.mailers.smtp.host')),
                'port' => data_get($meta, 'email.port', config('mail.mailers.smtp.port')),
                'encryption' => data_get($meta, 'email.encryption', config('mail.mailers.smtp.encryption')),
                'username' => data_get($meta, 'email.username', config('mail.mailers.smtp.username')),
                'password' => data_get($meta, 'email.password', config('mail.mailers.smtp.password')),
            ];

            Config::set('mail.default', $mailConfig['transport'] === 'smtp' ? 'smtp' : $mailConfig['transport']);
            if ($mailConfig['transport'] === 'smtp') {
                Config::set('mail.mailers.smtp.host', $mailConfig['host']);
                Config::set('mail.mailers.smtp.port', $mailConfig['port']);
                Config::set('mail.mailers.smtp.encryption', $mailConfig['encryption'] === 'null' ? null : $mailConfig['encryption']);
                Config::set('mail.mailers.smtp.username', $mailConfig['username']);
                Config::set('mail.mailers.smtp.password', $mailConfig['password']);
            }

            Config::set('mail.from.address', data_get($meta, 'email.from_address', config('mail.from.address')));
            Config::set('mail.from.name', data_get($meta, 'email.from_name', config('mail.from.name')));
        }

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['platform_admin', 'admin'])) {
                return true;
            }

            return null;
        });

        Gate::policy(Property::class, TenantOwnedPolicy::class);
        Gate::policy(Room::class, TenantOwnedPolicy::class);
        Gate::policy(RoomType::class, TenantOwnedPolicy::class);
        Gate::policy(Amenity::class, TenantOwnedPolicy::class);
        Gate::policy(Contract::class, TenantOwnedPolicy::class);
        Gate::policy(Invoice::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityMeter::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityRate::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityBill::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityMeterReading::class, TenantOwnedPolicy::class);
        Gate::policy(MaintenanceRequest::class, TenantOwnedPolicy::class);

        if (!app()->runningInConsole()) {
            $host = (string) request()->getHost();
            $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test');

            if ((bool) data_get($meta, 'utility.force_https', false) && !$isLocalHost) {
                URL::forceScheme('https');
            }
            $forwardedProto = request()->header('X-Forwarded-Proto');
            if ($forwardedProto === 'https') {
                URL::forceScheme('https');
            }
        }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event): void {
            $event->extendSocialite('google', GoogleProvider::class);
            $event->extendSocialite('telegram', TelegramProvider::class);
        });

        Event::listen(RentInvoiceCreated::class, SendRentInvoiceCreatedNotifications::class);
        Event::listen(RentInvoiceDueSoon::class, SendRentInvoiceDueSoonNotifications::class);
        Event::listen(RentInvoiceOverdue::class, SendRentInvoiceOverdueNotifications::class);
        Event::listen(ContractCreated::class, SendContractCreatedNotifications::class);
        Event::listen(ContractStatusChanged::class, SendContractStatusChangedNotifications::class);
        Event::listen(SubscriptionCreated::class, SendSubscriptionCreatedNotifications::class);
        Event::listen(SubscriptionTrialEndingSoon::class, SendSubscriptionTrialEndingNotifications::class);
        Event::listen(SubscriptionRenewalFailed::class, SendSubscriptionRenewalFailedNotifications::class);
        Event::listen(SubscriptionRenewalSucceeded::class, SendSubscriptionRenewalSucceededNotifications::class);
        Event::listen(SubscriptionCancelled::class, SendSubscriptionCancelledNotifications::class);
        Event::listen(SubscriptionExpired::class, SendSubscriptionExpiredNotifications::class);
        Event::listen(SubscriptionPaymentReceived::class, SendSubscriptionPaymentReceivedNotifications::class);
        Event::listen(SubscriptionPaymentFailed::class, SendSubscriptionPaymentFailedNotifications::class);
        Event::listen(SubscriptionPaymentRefunded::class, SendSubscriptionPaymentRefundedNotifications::class);
        Event::listen(TenantMemberInvited::class, SendTenantMemberInvitedNotifications::class);
        Event::listen(TenantMemberAccepted::class, SendTenantMemberAcceptedNotifications::class);
        Event::listen(TenantMemberDisabled::class, SendTenantMemberDisabledNotifications::class);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Event::listen('eloquent.*', function (string $eventName, array $data): void {
            $model = $data[0] ?? null;
            if (! $model instanceof Model) {
                return;
            }

            if ($model instanceof AuditLog) {
                return;
            }

            $action = Str::between($eventName, 'eloquent.', ':');
            if (! in_array($action, ['created', 'updated', 'deleted', 'restored'], true)) {
                return;
            }

            $before = null;
            $after = null;

            if ($action === 'created') {
                $after = $model->getAttributes();
            } elseif ($action === 'updated') {
                $changes = $model->getChanges();
                if (empty($changes)) {
                    return;
                }
                $before = array_intersect_key($model->getOriginal(), $changes);
                $after = $changes;
            } elseif ($action === 'deleted') {
                $before = $model->getOriginal();
            } elseif ($action === 'restored') {
                $after = $model->getAttributes();
            }

            $sensitive = ['password', 'remember_token'];
            $before = $before ? array_diff_key($before, array_flip($sensitive)) : null;
            $after = $after ? array_diff_key($after, array_flip($sensitive)) : null;

            app(AuditLogger::class)->log(
                $action,
                $model::class,
                (string) $model->getKey(),
                $before,
                $after,
                request(),
                data_get($model, 'tenant_id')
            );
        });

        View::composer('*', function ($view): void {
            $view->with('appSettings', BusinessSetting::current());
            if (class_exists(CurrentTenant::class)) {
                $view->with('currentTenant', app(CurrentTenant::class)->get());
            }
        });
    }
}
