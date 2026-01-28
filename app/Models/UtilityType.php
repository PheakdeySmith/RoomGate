<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenantOrGlobal;

class UtilityType extends Model
{
    use BelongsToTenantOrGlobal;
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'unit_of_measure',
        'billing_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(UtilityProvider::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(UtilityMeter::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(UtilityRate::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }
}
