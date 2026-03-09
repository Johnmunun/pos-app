<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ShippingMethodModel extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_shipping_methods';

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'type',
        'base_cost',
        'free_shipping_threshold',
        'zones',
        'weight_ranges',
        'price_ranges',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'zones' => 'array',
        'weight_ranges' => 'array',
        'price_ranges' => 'array',
        'is_active' => 'boolean',
    ];
}
