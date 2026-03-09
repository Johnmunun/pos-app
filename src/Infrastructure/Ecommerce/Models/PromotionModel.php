<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_promotions';

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'type',
        'discount_value',
        'buy_quantity',
        'get_quantity',
        'minimum_purchase',
        'maximum_uses',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
        'applicable_products',
        'applicable_categories',
        'customer_segments',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_purchase' => 'decimal:2',
        'maximum_uses' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'customer_segments' => 'array',
    ];
}
