<?php

namespace App\Listeners;

use App\Events\RentInvoiceCreated;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;

class SendRentInvoiceCreatedNotifications
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly InAppNotificationService $inApp
    ) {
    }

    public function handle(RentInvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $invoice->loadMissing(['tenant', 'contract.room.property', 'contract.occupant']);

        $tenant = $invoice->tenant;
        $contract = $invoice->contract;
        $occupant = $contract?->occupant;

        if ($occupant && $occupant->email) {
            $this->notifications->queue(
                'rent_invoice_created',
                $tenant,
                $occupant,
                [
                    'recipient_name' => $occupant->name,
                    'invoice_number' => $invoice->invoice_number,
                    'amount_due' => number_format(($invoice->total_cents ?? 0) / 100, 2),
                    'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                    'property_name' => $contract?->room?->property?->name ?? 'Property',
                    'room_number' => $contract?->room?->room_number ?? '-',
                ],
                [
                    'dedupe_key' => 'rent-invoice-created-'.$invoice->id.'-user-'.$occupant->id,
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract?->id,
                    ],
                ]
            );

            $this->inApp->create(
                $occupant,
                'New rent invoice created',
                'Invoice '.$invoice->invoice_number.' is ready.',
                [
                    'tenant_id' => $tenant?->id,
                    'dedupe_key' => 'rent-invoice-created-'.$invoice->id,
                    'type' => 'info',
                    'icon' => 'tabler-receipt-2',
                    'link_url' => $tenant ? route('Core.invoices.index', ['tenant' => $tenant->slug]) : null,
                ]
            );
        }

        if ($tenant) {
            $admins = $tenant->users()->wherePivotIn('role', ['owner', 'admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->email) {
                    $this->notifications->queue(
                        'rent_invoice_created_admin',
                        $tenant,
                        $admin,
                        [
                            'recipient_name' => $admin->name,
                            'invoice_number' => $invoice->invoice_number,
                            'amount_due' => number_format(($invoice->total_cents ?? 0) / 100, 2),
                            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                            'property_name' => $contract?->room?->property?->name ?? 'Property',
                            'room_number' => $contract?->room?->room_number ?? '-',
                            'occupant_name' => $occupant?->name ?? 'Tenant',
                        ],
                        [
                            'dedupe_key' => 'rent-invoice-created-'.$invoice->id.'-user-'.$admin->id,
                            'metadata' => [
                                'invoice_id' => $invoice->id,
                                'contract_id' => $contract?->id,
                            ],
                        ]
                    );
                }

                $this->inApp->create(
                    $admin,
                    'New invoice generated',
                    'Invoice '.$invoice->invoice_number.' was generated.',
                    [
                        'tenant_id' => $tenant->id,
                        'dedupe_key' => 'rent-invoice-created-'.$invoice->id,
                        'type' => 'info',
                        'icon' => 'tabler-receipt-2',
                        'link_url' => route('core.contracts.index', ['tenant' => $tenant->slug]),
                    ]
                );
            }
        }
    }
}
