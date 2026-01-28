<?php

namespace App\Listeners;

use App\Events\RentInvoiceOverdue;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;

class SendRentInvoiceOverdueNotifications
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly InAppNotificationService $inApp
    ) {
    }

    public function handle(RentInvoiceOverdue $event): void
    {
        $invoice = $event->invoice;
        $invoice->loadMissing(['tenant', 'contract.room.property', 'contract.occupant']);

        $tenant = $invoice->tenant;
        $contract = $invoice->contract;
        $occupant = $contract?->occupant;

        if ($occupant && $occupant->email) {
            $this->notifications->queue(
                'rent_invoice_overdue',
                $tenant,
                $occupant,
                [
                    'recipient_name' => $occupant->name,
                    'invoice_number' => $invoice->invoice_number,
                    'amount_due' => number_format((($invoice->total_cents ?? 0) - ($invoice->paid_cents ?? 0)) / 100, 2),
                    'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                    'property_name' => $contract?->room?->property?->name ?? 'Property',
                    'room_number' => $contract?->room?->room_number ?? '-',
                ],
                [
                    'dedupe_key' => 'rent-invoice-overdue-'.$invoice->id.'-'.now()->format('Ymd'),
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract?->id,
                    ],
                ]
            );

            $this->inApp->create(
                $occupant,
                'Rent invoice overdue',
                'Invoice '.$invoice->invoice_number.' is overdue.',
                [
                    'tenant_id' => $tenant?->id,
                    'type' => 'warning',
                    'icon' => 'tabler-alert-triangle',
                    'link_url' => $tenant ? route('Core.invoices.index', ['tenant' => $tenant->slug]) : null,
                ]
            );
        }
    }
}
