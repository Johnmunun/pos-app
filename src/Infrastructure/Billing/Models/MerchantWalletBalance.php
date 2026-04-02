<?php

namespace Src\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantWalletBalance extends Model
{
    protected $table = 'merchant_wallet_balances';

    protected $fillable = [
        'tenant_id',
        'currency_code',
        'available_balance',
        'pending_balance',
        'locked_balance',
    ];

    protected $casts = [
        'available_balance' => 'float',
        'pending_balance' => 'float',
        'locked_balance' => 'float',
    ];
}
