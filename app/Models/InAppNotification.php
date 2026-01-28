<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class InAppNotification extends Model
{
    use BelongsToTenant;
    protected $table = 'in_app_notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'body',
        'icon',
        'link_url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
