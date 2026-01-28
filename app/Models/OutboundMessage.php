<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class OutboundMessage extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'template_key',
        'to_address',
        'subject',
        'body',
        'status',
        'attempt_count',
        'last_error',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'bounced_at',
        'dedupe_key',
        'provider_message_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
