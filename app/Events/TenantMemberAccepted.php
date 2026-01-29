<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantMemberAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $member,
        public string $role
    ) {
    }
}
