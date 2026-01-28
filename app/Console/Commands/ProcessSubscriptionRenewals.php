<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:renew {--date= : Override date (Y-m-d)}';

    protected $description = 'Process subscription renewals and grace period expiration.';

    public function handle(): int
    {
        $today = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        $graceDays = (int) config('services.subscriptions.grace_days', 7);

        $dueSubscriptions = Subscription::query()
            ->with(['plan', 'tenant'])
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<=', $today->toDateString())
            ->get();

        $expired = 0;
        $renewed = 0;
        $graced = 0;

        foreach ($dueSubscriptions as $subscription) {
            if ($subscription->status === 'past_due' && $subscription->grace_period_ends_at) {
                if ($subscription->grace_period_ends_at->lt($today)) {
                    $subscription->update(['status' => 'expired']);
                    $expired++;
                }
                continue;
            }

            if (!$subscription->auto_renew) {
                $subscription->update(['status' => 'expired']);
                $expired++;
                continue;
            }

            $plan = $subscription->plan ?: Plan::query()->find($subscription->plan_id);
            if (!$plan) {
                $subscription->update(['status' => 'expired']);
                $expired++;
                continue;
            }

            DB::transaction(function () use ($subscription, $plan, $today, $graceDays, &$renewed, &$graced) {
                $periodStart = $today->copy();
                $periodEnd = $plan->interval === 'yearly'
                    ? $periodStart->copy()->addYear()
                    : $periodStart->copy()->addMonth();

                $subscription->update([
                    'status' => 'past_due',
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'grace_period_ends_at' => $today->copy()->addDays($graceDays),
                ]);

                $sequence = SubscriptionInvoice::query()
                    ->where('tenant_id', $subscription->tenant_id)
                    ->whereYear('billing_period_start', $periodStart->year)
                    ->count() + 1;

                SubscriptionInvoice::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'invoice_number' => sprintf('SUB-%s-%04d', $periodStart->format('Y'), $sequence),
                    'amount_cents' => $plan->price_cents,
                    'currency_code' => $plan->currency_code ?? 'USD',
                    'status' => 'sent',
                    'billing_period_start' => $periodStart->toDateString(),
                    'billing_period_end' => $periodEnd->toDateString(),
                    'due_date' => $today->copy()->addDays($graceDays)->toDateString(),
                ]);

                $renewed++;
                $graced++;
            });
        }

        $this->info("Renewed: {$renewed}, grace started: {$graced}, expired: {$expired}");

        return self::SUCCESS;
    }
}
