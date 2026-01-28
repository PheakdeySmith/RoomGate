<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class UtilityMeter extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'room_id',
        'utility_type_id',
        'provider_id',
        'meter_code',
        'unit_of_measure',
        'status',
        'installed_at',
        'last_reading_value',
        'last_reading_at',
        'extra_metadata',
    ];

    protected $casts = [
        'installed_at' => 'date',
        'last_reading_at' => 'datetime',
        'extra_metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function utilityType(): BelongsTo
    {
        return $this->belongsTo(UtilityType::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(UtilityProvider::class, 'provider_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(UtilityMeterReading::class, 'meter_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class, 'meter_id');
    }
}
