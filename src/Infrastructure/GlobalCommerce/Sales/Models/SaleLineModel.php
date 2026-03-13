<?php

namespace Src\Infrastructure\GlobalCommerce\Sales\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $product_id
 * @property float $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property string $product_name
 *
 * // Champs agrégés pour les rapports
 * @property-read float|null $qty_sold
 * @property-read float|null $revenue
 */
class SaleLineModel extends Model
{
    protected $table = 'gc_sale_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'product_name',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'subtotal' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(SaleModel::class, 'sale_id');
    }
}
