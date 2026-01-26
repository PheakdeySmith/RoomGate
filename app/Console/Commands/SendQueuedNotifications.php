<?php

namespace App\Console\Commands;

use App\Jobs\SendOutboundMessage;
use App\Models\OutboundMessage;
use Illuminate\Console\Command;

class SendQueuedNotifications extends Command
{
    protected $signature = 'notifications:send-queued {--limit=100 : Max messages to dispatch}';

    protected $description = 'Dispatch queued outbound notifications.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $messages = OutboundMessage::query()
            ->where('status', 'queued')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No queued notifications.');
            return self::SUCCESS;
        }

        foreach ($messages as $message) {
            SendOutboundMessage::dispatch($message->id);
        }

        $this->info('Dispatched '.$messages->count().' notifications.');

        return self::SUCCESS;
    }
}
