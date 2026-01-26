<?php

namespace App\Jobs;

use App\Models\OutboundMessage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOutboundMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(public int $messageId)
    {
    }

    public function handle(): void
    {
        $message = OutboundMessage::query()->find($this->messageId);
        if (!$message || $message->status === 'sent') {
            return;
        }

        $message->update([
            'status' => 'sending',
            'attempt_count' => $message->attempt_count + 1,
            'last_error' => null,
        ]);

        try {
            if ($message->channel !== 'email') {
                throw new Exception('Unsupported channel: '.$message->channel);
            }

            Mail::html($message->body, function ($mail) use ($message) {
                $mail->to($message->to_address)
                    ->subject($message->subject ?? 'Notification');
            });

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Exception $exception) {
            $message->update([
                'status' => $message->attempt_count >= $this->tries ? 'failed' : 'queued',
                'last_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
