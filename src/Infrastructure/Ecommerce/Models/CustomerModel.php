<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property int $shop_id
 * @property string|null $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $default_shipping_address
 * @property string|null $default_billing_address
 * @property int $total_orders
 * @property float $total_spent
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
