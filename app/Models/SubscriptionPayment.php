<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class SubscriptionPayment extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'subscription_invoice_id',
        'amount_cents',
        'currency_code',
        'provider',
        'provider_ref',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }
}
