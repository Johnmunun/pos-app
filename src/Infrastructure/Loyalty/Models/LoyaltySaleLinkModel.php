<?php

namespace Src\Infrastructure\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltySaleLinkModel extends Model
{
    protected $table = 'loyalty_sale_links';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'module',
        'sale_id',
        'loyalty_account_id',
        'customer_id',
        'points_earned',
        'points_redeemed',
        'discount_amount',
        'eligible_amount',
        'status',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'points_earned' => 'integer',
        'points_redeemed' => 'integer',
        'discount_amount' => 'decimal:2',
        'eligible_amount' => 'decimal:2',
    ];
}
