<?php

namespace Src\Infrastructure\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransactionModel extends Model
{
    protected $table = 'loyalty_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'loyalty_account_id',
        'tenant_id',
        'type',
        'points',
        'balance_after',
        'module',
        'sale_id',
        'description',
        'meta',
        'expires_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'points' => 'integer',
        'balance_after' => 'integer',
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];
}
