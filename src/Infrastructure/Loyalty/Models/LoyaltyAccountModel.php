<?php

namespace Src\Infrastructure\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccountModel extends Model
{
    protected $table = 'loyalty_accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'module',
        'customer_id',
        'loyalty_number',
        'tier',
        'points_balance',
        'lifetime_points',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransactionModel::class, 'loyalty_account_id');
    }
}
