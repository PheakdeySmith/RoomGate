<?php

namespace App\Services;

use App\Models\OutboundMessage;

class OutboundMessageStatusService
{
    public function markBounced(OutboundMessage $message, ?string $reason = null): void
    {
        $message->update([
            'status' => 'bounced',
            'bounced_at' => now(),
            'last_error' => $reason,
        ]);
    }

    public function markFailed(OutboundMessage $message, ?string $reason = null): void
    {
        $message->update([
            'status' => 'failed',
            'failed_at' => now(),
            'last_error' => $reason,
        ]);
    }
}
