<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'model_type',
        'model_id',
        'tenant_id',
        'before_json',
        'after_json',
        'user_id',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
