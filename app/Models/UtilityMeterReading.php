<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class UtilityMeterReading extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'meter_id',
        'reading_value',
        'reading_at',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'reading_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(UtilityMeter::class, 'meter_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
