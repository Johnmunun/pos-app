<?php

namespace Src\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantWithdrawalRequest extends Model
{
    protected $table = 'merchant_withdrawal_requests';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'currency_code',
        'requested_amount',
        'fee_amount',
        'net_amount',
        'destination_type',
        'destination_reference',
        'status',
        'approved_by_user_id',
        'approved_at',
        'paid_at',
        'rejection_reason',
        'meta',
    ];

    protected $casts = [
        'requested_amount' => 'float',
        'fee_amount' => 'float',
        'net_amount' => 'float',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];
}
