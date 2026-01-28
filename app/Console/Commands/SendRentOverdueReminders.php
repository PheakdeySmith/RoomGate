<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Events\RentInvoiceOverdue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendRentOverdueReminders extends Command
{
    protected $signature = 'rent:send-overdue-reminders';

    protected $description = 'Queue overdue rent invoice reminders.';

    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();

        $invoices = Invoice::query()
            ->with(['tenant', 'contract.room.property', 'contract.occupant'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereRaw('paid_cents < total_cents')
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where(function ($query) use ($today) {
                $query->whereNull('next_reminder_at')
                    ->orWhereDate('next_reminder_at', '<=', $today->toDateString());
            })
            ->get();

        $queued = 0;

        foreach ($invoices as $invoice) {
            DB::transaction(function () use ($invoice, $today) {
                $invoice->update([
                    'status' => 'overdue',
                    'last_reminder_at' => $today,
                    'next_reminder_at' => $today->copy()->addDays(7),
                    'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                ]);
            });

            event(new RentInvoiceOverdue($invoice));

            $queued++;
        }

        $this->info("Queued {$queued} overdue reminder(s).");

        return self::SUCCESS;
    }
}
