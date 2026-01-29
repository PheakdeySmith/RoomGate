<?php

namespace App\Listeners;

use App\Events\RentInvoiceDueSoon;
use App\Models\User;
use App\Services\NotificationService;

class SendRentInvoiceDueSoonNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(RentInvoiceDueSoon $event): void
    {
        $invoice = $event->invoice;
        $invoice->loadMissing(['tenant.users', 'contract.room.property', 'contract.occupant']);

        $tenant = $invoice->tenant;
        $contract = $invoice->contract;
        $occupant = $contract?->occupant;

        $amountDue = number_format((($invoice->total_cents ?? 0) - ($invoice->paid_cents ?? 0)) / 100, 2);
        $dueDate = optional($invoice->due_date)->format('Y-m-d');
        $propertyName = $contract?->room?->property?->name ?? 'Property';
        $roomNumber = $contract?->room?->room_number ?? '-';

        if ($occupant && $occupant->email) {
            $this->notifications->queue(
                'rent_invoice_due_soon',
                $tenant,
                $occupant,
                [
                    'recipient_name' => $occupant->name,
                    'invoice_number' => $invoice->invoice_number,
                    'amount_due' => $amountDue,
                    'due_date' => $dueDate,
                    'property_name' => $propertyName,
                    'room_number' => $roomNumber,
                ],
                [
                    'dedupe_key' => 'rent-invoice-due-soon-'.$invoice->id.'-user-'.$occupant->id,
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract?->id,
                    ],
                ]
            );
        }

        if ($tenant) {
            $admins = $this->resolveAdmins($tenant->users);
            foreach ($admins as $admin) {
                if (! $admin->email) {
                    continue;
                }

                $this->notifications->queue(
                    'rent_invoice_due_soon_admin',
                    $tenant,
                    $admin,
                    [
                        'recipient_name' => $admin->name,
                        'invoice_number' => $invoice->invoice_number,
                        'amount_due' => $amountDue,
                        'due_date' => $dueDate,
                        'property_name' => $propertyName,
                        'room_number' => $roomNumber,
                        'occupant_name' => $occupant?->name ?? 'Tenant',
                    ],
                    [
                        'dedupe_key' => 'rent-invoice-due-soon-'.$invoice->id.'-user-'.$admin->id,
                        'metadata' => [
                            'invoice_id' => $invoice->id,
                            'contract_id' => $contract?->id,
                        ],
                    ]
                );
            }
        }
    }

    private function resolveAdmins($users)
    {
        return $users->filter(function (User $user) {
            return in_array($user->pivot?->role, ['owner', 'admin'], true);
        });
    }
}
