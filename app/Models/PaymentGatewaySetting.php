<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $fillable = [
        'gateway_name',
        'is_active',
        'gateway_username',
        'gateway_password',
        'gateway_signature',
        'gateway_client_id',
        'gateway_mode',
        'gateway_secret_key',
        'gateway_publisher_key',
        'gateway_private_key',
        'merchant_id',
        'webhook_secret',
        'service_charge',
        'charge_type',
        'charge',
        'health_status',
        'health_message',
        'health_checked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_charge' => 'boolean',
        'charge' => 'decimal:2',
        'health_checked_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function ensureDefaults(): void
    {
        foreach (['paypal', 'stripe', 'bakong'] as $gateway) {
            static::query()->firstOrCreate(
                ['gateway_name' => $gateway],
                ['gateway_mode' => 'sandbox', 'is_active' => false]
            );
        }
    }
}
