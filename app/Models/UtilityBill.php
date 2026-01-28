<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class UtilityBill extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'property_id',
        'room_id',
        'utility_type_id',
        'meter_id',
        'provider_id',
        'billing_period_start',
        'billing_period_end',
        'start_reading_id',
        'end_reading_id',
        'usage_amount',
        'unit_cost_cents',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency_code',
        'status',
        'issued_at',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'issued_at' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
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

    public function meter(): BelongsTo
    {
        return $this->belongsTo(UtilityMeter::class, 'meter_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(UtilityProvider::class, 'provider_id');
    }

    public function startReading(): BelongsTo
    {
        return $this->belongsTo(UtilityMeterReading::class, 'start_reading_id');
    }

    public function endReading(): BelongsTo
    {
        return $this->belongsTo(UtilityMeterReading::class, 'end_reading_id');
    }
}
