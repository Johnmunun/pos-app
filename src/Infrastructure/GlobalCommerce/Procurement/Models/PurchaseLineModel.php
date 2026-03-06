<?php

namespace Src\Infrastructure\GlobalCommerce\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $purchase_id
 * @property string $product_id
 * @property float $ordered_quantity
 * @property float $received_quantity
 * @property float $unit_cost
 * @property float $line_total
 * @property string $product_name
 */
class PurchaseLineModel extends Model
{
    protected $table = 'gc_purchase_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'purchase_id', 'product_id', 'ordered_quantity', 'received_quantity',
        'unit_cost', 'line_total', 'product_name',
    ];

    protected $casts = [
        'ordered_quantity' => 'float',
        'received_quantity' => 'float',
        'unit_cost' => 'float',
        'line_total' => 'float',
    ];

    public function purchase()
    {
        return $this->belongsTo(PurchaseModel::class, 'purchase_id');
    }
}
