<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenantOrGlobal;

class PropertyType extends Model
{
    use BelongsToTenantOrGlobal;
    protected $fillable = [
        'tenant_id',
        'name',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
