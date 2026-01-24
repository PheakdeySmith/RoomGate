<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    public function queue(
        string $templateKey,
        ?Tenant $tenant,
        ?User $user,
        array $data = [],
        array $options = []
    ): ?OutboundMessage {
        $template = $this->resolveTemplate($templateKey, $tenant);
        if (!$template) {
            return null;
        }

        $subject = $this->replaceTokens($template->subject ?? '', $data);
        $body = $this->replaceTokens($template->body, $data);
        $toAddress = $options['to_address'] ?? ($user?->email);

        return OutboundMessage::create([
            'tenant_id' => $tenant?->id,
            'user_id' => $user?->id,
            'channel' => $options['channel'] ?? $template->channel ?? 'email',
            'template_key' => $templateKey,
            'to_address' => $toAddress,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
            'scheduled_at' => $options['scheduled_at'] ?? Carbon::now(),
            'dedupe_key' => $options['dedupe_key'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    private function resolveTemplate(string $key, ?Tenant $tenant): ?MessageTemplate
    {
        return MessageTemplate::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->orderByRaw('tenant_id is null')
            ->when($tenant, function ($query) use ($tenant) {
                $query->where(function ($sub) use ($tenant) {
                    $sub->where('tenant_id', $tenant->id)
                        ->orWhereNull('tenant_id');
                });
            })
            ->when(!$tenant, function ($query) {
                $query->whereNull('tenant_id');
            })
            ->first();
    }

    private function replaceTokens(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }

        return $text;
    }
}
