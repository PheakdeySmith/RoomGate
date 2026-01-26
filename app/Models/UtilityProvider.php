<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UtilityProvider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'utility_type_id',
        'name',
        'account_number',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'notes',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(UtilityMeter::class, 'provider_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class, 'provider_id');
    }
}
