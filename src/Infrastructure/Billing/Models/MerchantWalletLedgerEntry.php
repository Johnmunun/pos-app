<?php

namespace Src\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantWalletLedgerEntry extends Model
{
    protected $table = 'merchant_wallet_ledger_entries';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'billing_payment_transaction_id',
        'ecommerce_order_id',
        'entry_type',
        'direction',
        'currency_code',
        'gross_amount',
        'platform_fee_amount',
        'gateway_fee_amount',
        'net_amount',
        'running_available_balance',
        'running_pending_balance',
        'running_locked_balance',
        'meta',
    ];

    protected $casts = [
        'gross_amount' => 'float',
        'platform_fee_amount' => 'float',
        'gateway_fee_amount' => 'float',
        'net_amount' => 'float',
        'running_available_balance' => 'float',
        'running_pending_balance' => 'float',
        'running_locked_balance' => 'float',
        'meta' => 'array',
    ];
}
