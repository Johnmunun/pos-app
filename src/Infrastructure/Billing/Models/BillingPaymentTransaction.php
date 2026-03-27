<?php

namespace Src\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPaymentTransaction extends Model
{
    protected $table = 'billing_payment_transactions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'billing_plan_id',
        'ecommerce_order_id',
        'provider',
        'payment_method',
        'amount',
        'currency_code',
        'status',
        'provider_reference',
        'checkout_url',
        'provider_payload',
        'metadata',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'provider_payload' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
