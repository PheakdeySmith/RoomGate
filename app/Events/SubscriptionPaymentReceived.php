<?php

namespace App\Events;

use App\Models\SubscriptionPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SubscriptionPayment $payment)
    {
    }
}
