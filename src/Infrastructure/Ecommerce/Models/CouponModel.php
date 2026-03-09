<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_coupons';

    protected $fillable = [
        'shop_id',
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'minimum_purchase',
        'maximum_uses',
        'maximum_uses_per_customer',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
        'applicable_products',
        'applicable_categories',
        'excluded_products',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_purchase' => 'decimal:2',
        'maximum_uses' => 'integer',
        'maximum_uses_per_customer' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
    ];
}
