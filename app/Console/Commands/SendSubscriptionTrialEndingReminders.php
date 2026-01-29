<?php

namespace App\Console\Commands;

use App\Events\SubscriptionTrialEndingSoon;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSubscriptionTrialEndingReminders extends Command
{
    protected $signature = 'subscriptions:send-trial-ending {--days= : Days before trial end}';

    protected $description = 'Queue trial ending reminders for subscriptions.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('services.notifications.trial_ending_days', 3));
        $today = Carbon::now()->startOfDay();
        $targetDate = $today->copy()->addDays($days);

        $subscriptions = Subscription::query()
            ->with(['tenant', 'plan'])
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $targetDate->toDateString())
            ->get();

        $queued = 0;

        foreach ($subscriptions as $subscription) {
            event(new SubscriptionTrialEndingSoon($subscription));
            $queued++;
        }

        $this->info("Queued {$queued} trial ending reminder(s).");

        return self::SUCCESS;
    }
}
