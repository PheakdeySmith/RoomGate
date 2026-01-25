<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\URL;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Google\Provider as GoogleProvider;
use SocialiteProviders\Telegram\Provider as TelegramProvider;

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

            $request = request();

            AuditLog::create([
                'action' => $action,
                'model_type' => $model::class,
                'model_id' => (string) $model->getKey(),
                'before_json' => $before,
                'after_json' => $after,
                'user_id' => Auth::id(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
            ]);
        });

        View::composer('*', function ($view): void {
            $view->with('appSettings', BusinessSetting::current());
        });
    }
}
