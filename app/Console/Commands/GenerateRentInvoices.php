<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Events\RentInvoiceCreated;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRentInvoices extends Command
{
    protected $signature = 'rent:generate-invoices {--date= : Override issue date (Y-m-d)}';

    protected $description = 'Generate rent invoices for active contracts that are due.';

    public function handle(): int
    {
        $issueDate = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        $contracts = Contract::query()
            ->with(['tenant', 'room.property', 'occupant'])
            ->where('status', 'active')
            ->where(function ($query) use ($issueDate) {
                $query->whereNull('next_invoice_date')
                    ->orWhereDate('next_invoice_date', '<=', $issueDate->toDateString());
            })
            ->get();

        $created = 0;

        foreach ($contracts as $contract) {
            $dueDate = $this->resolveDueDate($contract, $issueDate);
            $nextInvoiceDate = $this->resolveNextInvoiceDate($contract, $issueDate);

            $exists = Invoice::query()
                ->where('contract_id', $contract->id)
                ->whereDate('issue_date', $issueDate->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            $invoice = DB::transaction(function () use ($contract, $issueDate, $dueDate, $nextInvoiceDate) {
                $sequence = Invoice::where('tenant_id', $contract->tenant_id)
                    ->whereYear('issue_date', $issueDate->year)
                    ->count() + 1;
                $invoiceNumber = sprintf('INV-%s-%04d', $issueDate->format('Y'), $sequence);

                $invoice = Invoice::create([
                    'tenant_id' => $contract->tenant_id,
                    'contract_id' => $contract->id,
                    'invoice_number' => $invoiceNumber,
                    'issue_date' => $issueDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'currency_code' => $contract->currency_code ?? 'USD',
                    'subtotal_cents' => $contract->monthly_rent_cents,
                    'discount_cents' => 0,
                    'total_cents' => $contract->monthly_rent_cents,
                    'paid_cents' => 0,
                    'status' => 'sent',
                    'sent_at' => $issueDate,
                ]);

                InvoiceItem::create([
                    'tenant_id' => $contract->tenant_id,
                    'invoice_id' => $invoice->id,
                    'description' => 'Monthly rent',
                    'amount_cents' => $contract->monthly_rent_cents,
                    'item_type' => 'rent',
                ]);

                $contract->update([
                    'last_invoiced_through' => $issueDate->toDateString(),
                    'next_invoice_date' => $nextInvoiceDate->toDateString(),
                ]);

                return $invoice;
            });

            event(new RentInvoiceCreated($invoice));
            $created++;
        }

        $this->info("Generated {$created} invoice(s).");

        return self::SUCCESS;
    }

    private function resolveDueDate(Contract $contract, Carbon $issueDate): Carbon
    {
        if (in_array($contract->billing_cycle, ['weekly', 'daily'], true)) {
            return $contract->billing_cycle === 'weekly'
                ? $issueDate->copy()->addDays(7)
                : $issueDate->copy()->addDay();
        }

        $dueDate = $issueDate->copy()->day(min($contract->payment_due_day, $issueDate->daysInMonth));
        if ($dueDate->lt($issueDate)) {
            $dueDate = $dueDate->addMonthNoOverflow();
        }

        return $dueDate;
    }

    private function resolveNextInvoiceDate(Contract $contract, Carbon $issueDate): Carbon
    {
        return match ($contract->billing_cycle) {
            'weekly' => $issueDate->copy()->addWeek(),
            'daily' => $issueDate->copy()->addDay(),
            default => $issueDate->copy()->addMonthNoOverflow(),
        };
    }
}
