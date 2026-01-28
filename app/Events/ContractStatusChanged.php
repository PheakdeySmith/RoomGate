<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Contract $contract, public ?string $previousStatus)
    {
    }
}
