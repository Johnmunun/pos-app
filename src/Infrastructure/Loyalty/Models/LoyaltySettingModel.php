<?php

namespace Src\Infrastructure\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltySettingModel extends Model
{
    protected $table = 'loyalty_settings';

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'enabled',
        'earn_amount_per_point',
        'points_per_earn_unit',
        'redeem_value_per_point',
        'min_points_redeem',
        'max_discount_percent',
        'points_expire_days',
        'tier_thresholds',
        'module_rules',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'earn_amount_per_point' => 'decimal:4',
        'points_per_earn_unit' => 'integer',
        'redeem_value_per_point' => 'decimal:4',
        'min_points_redeem' => 'integer',
        'max_discount_percent' => 'decimal:2',
        'points_expire_days' => 'integer',
        'tier_thresholds' => 'array',
        'module_rules' => 'array',
    ];
}
