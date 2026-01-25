<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthIdentity extends Model
{
    protected $table = 'auth_identities';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'meta_json',
        'raw_profile_json',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta_json' => 'array',
        'raw_profile_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
