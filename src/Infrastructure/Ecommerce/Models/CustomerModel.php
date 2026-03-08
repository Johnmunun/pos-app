<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_customers';

    protected $fillable = [
        'id',
        'shop_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'default_shipping_address',
        'default_billing_address',
        'total_orders',
        'total_spent',
        'is_active',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
