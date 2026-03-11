<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property int $shop_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string $type
 * @property array<mixed>|null $config
 * @property float|null $fee_percentage
 * @property float|null $fee_fixed
 * @property bool $is_active
 * @property bool $is_default
 * @property int|null $sort_order
 */
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
