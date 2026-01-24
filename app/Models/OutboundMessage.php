<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundMessage extends Model
{
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
        'dedupe_key',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
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
