<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $stock_transfer_id
 * @property string $product_id
 * @property float $quantity
 * @property-read GcStockTransferModel|null $transfer
 * @property-read ProductModel|null $product
 */
class GcStockTransferItemModel extends Model
{
    protected $table = 'gc_stock_transfer_items';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'stock_transfer_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(GcStockTransferModel::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function scopeByTransfer($query, string $transferId)
    {
        return $query->where('stock_transfer_id', $transferId);
    }
}
