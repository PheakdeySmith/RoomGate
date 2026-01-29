<?php

namespace App\Console\Commands;

use App\Events\RentInvoiceDueSoon;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRentDueSoonReminders extends Command
{
    protected $signature = 'rent:send-due-soon-reminders {--days= : Days before due date}';

    protected $description = 'Queue upcoming rent invoice reminders.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('services.notifications.invoice_due_soon_days', 3));
        $today = Carbon::now()->startOfDay();
        $targetDate = $today->copy()->addDays($days);

        $invoices = Invoice::query()
            ->with(['tenant', 'contract.room.property', 'contract.occupant'])
            ->whereDate('due_date', $targetDate->toDateString())
            ->whereRaw('paid_cents < total_cents')
            ->whereIn('status', ['sent', 'partial'])
            ->get();

        $queued = 0;

        foreach ($invoices as $invoice) {
            event(new RentInvoiceDueSoon($invoice));
            $queued++;
        }

        $this->info("Queued {$queued} due soon reminder(s).");

        return self::SUCCESS;
    }
}
