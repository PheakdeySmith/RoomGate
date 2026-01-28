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
use App\Models\UtilityProvider;
use App\Models\UtilityRate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\URL;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Google\Provider as GoogleProvider;
use SocialiteProviders\Telegram\Provider as TelegramProvider;
use Modules\Core\App\Services\CurrentTenant;
use App\Policies\TenantOwnedPolicy;
use App\Services\AuditLogger;
use App\Events\RentInvoiceCreated;
use App\Events\RentInvoiceOverdue;
use App\Events\ContractCreated;
use App\Events\ContractStatusChanged;
use App\Listeners\SendRentInvoiceCreatedNotifications;
use App\Listeners\SendRentInvoiceOverdueNotifications;
use App\Listeners\SendContractCreatedNotifications;
use App\Listeners\SendContractStatusChangedNotifications;

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
        Gate::policy(UtilityProvider::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityMeter::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityRate::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityBill::class, TenantOwnedPolicy::class);
        Gate::policy(UtilityMeterReading::class, TenantOwnedPolicy::class);

        if (!app()->runningInConsole()) {
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
        Event::listen(RentInvoiceOverdue::class, SendRentInvoiceOverdueNotifications::class);
        Event::listen(ContractCreated::class, SendContractCreatedNotifications::class);
        Event::listen(ContractStatusChanged::class, SendContractStatusChangedNotifications::class);

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
