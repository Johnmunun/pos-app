<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float|null $discount_value
 * @property float|null $minimum_purchase
 * @property int|null $maximum_uses
 * @property int|null $maximum_uses_per_customer
 * @property int $used_count
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $is_active
 * @property array<int,string>|null $applicable_products
 * @property array<int,string>|null $applicable_categories
 * @property array<int,string>|null $excluded_products
 */
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
