<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $product_id
 * @property int|float $quantity
 * @property float $unit_price_amount
 * @property string $currency
 * @property float $line_total_amount
 * @property float|null $discount_percent
 * @property \Carbon\Carbon $created_at
 * @property-read ProductModel $product
 */
class SaleLineModel extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_sale_lines';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'quantity',
        'unit_price_amount',
        'currency',
        'line_total_amount',
        'discount_percent',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price_amount' => 'decimal:2',
        'line_total_amount' => 'decimal:2',
        'discount_percent' => 'float',
    ];

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'sale_id');
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}

