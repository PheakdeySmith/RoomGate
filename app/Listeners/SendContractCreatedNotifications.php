<?php

namespace App\Listeners;

use App\Events\ContractCreated;
use App\Services\InAppNotificationService;

class SendContractCreatedNotifications
{
    public function __construct(private readonly InAppNotificationService $inApp)
    {
    }

    public function handle(ContractCreated $event): void
    {
        $contract = $event->contract->loadMissing(['tenant.users', 'room.property', 'occupant']);
        $tenant = $contract->tenant;

        if (!$tenant) {
            return;
        }

        $occupant = $contract->occupant;
        $admins = $tenant->users()->wherePivotIn('role', ['owner', 'admin'])->get();
        $title = 'Contract created';
        $body = $contract->room
            ? 'Contract created for room '.$contract->room->room_number.'.'
            : 'A new contract was created.';

        foreach ($admins as $admin) {
            $this->inApp->create($admin, $title, $body, [
                'tenant_id' => $tenant->id,
                'type' => 'info',
                'icon' => 'tabler-file-text',
                'link_url' => route('core.contracts.index'),
            ]);
        }

        if ($occupant) {
            $this->inApp->create($occupant, 'Your contract is active', $body, [
                'tenant_id' => $tenant->id,
                'type' => 'success',
                'icon' => 'tabler-file-text',
            ]);
        }
    }
}
