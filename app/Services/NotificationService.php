<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

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

        $attributes = [
            'tenant_id' => $tenant?->id,
            'dedupe_key' => $options['dedupe_key'] ?? null,
        ];

        $values = [
            'user_id' => $user?->id,
            'channel' => $options['channel'] ?? $template->channel ?? 'email',
            'template_key' => $templateKey,
            'to_address' => $toAddress,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
            'scheduled_at' => $options['scheduled_at'] ?? Carbon::now(),
            'metadata' => $options['metadata'] ?? null,
        ];

        if (! empty($attributes['dedupe_key'])) {
            try {
                return OutboundMessage::firstOrCreate($attributes, $values);
            } catch (QueryException $exception) {
                if ($this->isDedupeConflict($exception)) {
                    return OutboundMessage::where($attributes)->first();
                }
                throw $exception;
            }
        }

        return OutboundMessage::create(array_merge($attributes, $values));
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

    private function isDedupeConflict(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' && in_array($driverCode, [1062, 23505], true);
    }
}
