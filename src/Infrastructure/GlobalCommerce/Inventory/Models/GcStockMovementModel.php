<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Mouvement de stock GlobalCommerce (gc_stock_movements).
 *
 * @property string $id
 * @property int $shop_id
 * @property string $product_id
 * @property string $type
 * @property float $quantity
 * @property string|null $reference
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property int|null $created_by
 */
class GcStockMovementModel extends Model
{
    protected $table = 'gc_stock_movements';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'product_id',
        'type',
        'quantity',
        'reference',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'created_by' => 'integer',
        'shop_id' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}

