<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentMethodModel extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_payment_methods';

    protected $fillable = [
        'shop_id',
        'name',
        'code',
        'description',
        'type',
        'config',
        'fee_percentage',
        'fee_fixed',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'fee_percentage' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
