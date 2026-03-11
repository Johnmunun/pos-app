<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property string $product_name
 * @property string|null $product_sku
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount_amount
 * @property float $subtotal
 * @property string|null $product_image_url
 * @property bool $is_digital
 * @property string|null $download_token
 * @property \Illuminate\Support\Carbon|null $download_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read OrderModel $order
 */
class OrderItemModel extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_order_items';

    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'subtotal',
        'product_image_url',
        'is_digital',
        'download_token',
        'download_expires_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_digital' => 'boolean',
        'download_expires_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
}
