<?php

namespace App\Listeners;

use App\Events\ContractStatusChanged;
use App\Services\InAppNotificationService;

class SendContractStatusChangedNotifications
{
    public function __construct(private readonly InAppNotificationService $inApp)
    {
    }

    public function handle(ContractStatusChanged $event): void
    {
        $contract = $event->contract->loadMissing(['tenant.users', 'room.property', 'occupant']);
        $tenant = $contract->tenant;

        if (!$tenant || $event->previousStatus === $contract->status) {
            return;
        }

        $occupant = $contract->occupant;
        $admins = $tenant->users()->wherePivotIn('role', ['owner', 'admin'])->get();
        $title = 'Contract status updated';
        $body = 'Contract status changed to '.ucfirst((string) $contract->status).'.';

        foreach ($admins as $admin) {
            $this->inApp->create($admin, $title, $body, [
                'tenant_id' => $tenant->id,
                'type' => 'warning',
                'icon' => 'tabler-alert-triangle',
                'link_url' => route('core.contracts.index'),
            ]);
        }

        if ($occupant) {
            $this->inApp->create($occupant, $title, $body, [
                'tenant_id' => $tenant->id,
                'type' => 'warning',
                'icon' => 'tabler-alert-triangle',
            ]);
        }
    }
}
