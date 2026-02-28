<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $purchase_order_id
 * @property string $product_id
 * @property float|string $ordered_quantity
 * @property float|string $received_quantity
 * @property float|string $unit_cost_amount
 * @property string $currency
 * @property float|string $line_total_amount
 * @property \Carbon\Carbon $created_at
 */
class PurchaseOrderLineModel extends Model
{
    protected $table = 'quincaillerie_purchase_order_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'purchase_order_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
        'unit_cost_amount',
        'currency',
        'line_total_amount',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_cost_amount' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
