<?php

namespace App\Console\Commands;

use App\Models\OutboundMessage;
use Illuminate\Console\Command;

class RetryFailedOutboundMessages extends Command
{
    protected $signature = 'notifications:retry-failed {--limit=200 : Max messages to retry} {--max-attempts=5 : Max attempts before giving up}';

    protected $description = 'Retry failed outbound messages by moving them back to queued.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $maxAttempts = (int) $this->option('max-attempts');

        $messages = OutboundMessage::query()
            ->where('status', 'failed')
            ->where('attempt_count', '<', $maxAttempts)
            ->orderBy('failed_at')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No failed messages to retry.');
            return self::SUCCESS;
        }

        foreach ($messages as $message) {
            $message->update([
                'status' => 'queued',
                'last_error' => null,
                'scheduled_at' => now(),
            ]);
        }

        $this->info('Re-queued '.$messages->count().' failed message(s).');

        return self::SUCCESS;
    }
}
