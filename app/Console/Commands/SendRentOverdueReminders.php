<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\NotificationService;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendRentOverdueReminders extends Command
{
    protected $signature = 'rent:send-overdue-reminders';

    protected $description = 'Queue overdue rent invoice reminders.';

    public function handle(NotificationService $notifications, InAppNotificationService $inApp): int
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
            $occupant = $invoice->contract?->occupant;
            if (!$occupant || !$occupant->email) {
                continue;
            }

            $notifications->queue(
                'rent_invoice_overdue',
                $invoice->tenant,
                $occupant,
                [
                    'recipient_name' => $occupant->name,
                    'invoice_number' => $invoice->invoice_number,
                    'amount_due' => number_format(($invoice->total_cents - $invoice->paid_cents) / 100, 2),
                    'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                    'property_name' => $invoice->contract?->room?->property?->name ?? 'Property',
                    'room_number' => $invoice->contract?->room?->room_number ?? '—',
                ],
                [
                    'dedupe_key' => 'rent-invoice-overdue-'.$invoice->id.'-'.$today->format('Ymd'),
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'contract_id' => $invoice->contract?->id,
                    ],
                ]
            );

            $inApp->create(
                $occupant,
                'Rent invoice overdue',
                'Invoice '.$invoice->invoice_number.' is overdue.',
                [
                    'tenant_id' => $invoice->tenant?->id,
                    'type' => 'warning',
                    'icon' => 'tabler-alert-triangle',
                    'link_url' => route('admin.invoices.index'),
                ]
            );

            DB::transaction(function () use ($invoice, $today) {
                $invoice->update([
                    'status' => 'overdue',
                    'last_reminder_at' => $today,
                    'next_reminder_at' => $today->copy()->addDays(7),
                    'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                ]);
            });

            $queued++;
        }

        $this->info("Queued {$queued} overdue reminder(s).");

        return self::SUCCESS;
    }
}
