<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'occupant_user_id',
        'room_id',
        'start_date',
        'end_date',
        'monthly_rent_cents',
        'deposit_cents',
        'currency_code',
        'billing_cycle',
        'payment_due_day',
        'next_invoice_date',
        'last_invoiced_through',
        'contract_image_path',
        'status',
        'notes',
        'auto_renew',
        'terminated_at',
        'termination_reason',
        'previous_contract_id',
        'auto_payment',
        'payment_method_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'last_invoiced_through' => 'date',
        'terminated_at' => 'datetime',
        'auto_renew' => 'boolean',
        'auto_payment' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function occupant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'occupant_user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
