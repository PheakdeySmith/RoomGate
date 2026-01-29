<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'maintenance_request_id',
        'vendor_name',
        'scheduled_for',
        'completed_at',
        'cost_cents',
        'currency_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }
}
