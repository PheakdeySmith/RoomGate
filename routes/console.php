<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rent:generate-invoices')->dailyAt('01:00');
Schedule::command('rent:send-due-soon-reminders')->dailyAt('00:30');
Schedule::command('rent:send-overdue-reminders')->dailyAt('02:00');
Schedule::command('notifications:send-queued')->everyFiveMinutes();
Schedule::command('notifications:retry-failed')->hourly();
Schedule::command('subscriptions:renew')->dailyAt('03:00');
Schedule::command('subscriptions:send-trial-ending')->dailyAt('00:45');
Schedule::command('rooms:reconcile-status')->dailyAt('02:30');
