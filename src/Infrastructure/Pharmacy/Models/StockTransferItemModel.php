<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $stock_transfer_id
 * @property string $product_id
 * @property int $quantity
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read StockTransferModel|null $transfer
 * @property-read ProductModel|null $product
 */
class StockTransferItemModel extends Model
{
    protected $table = 'pharmacy_stock_transfer_items';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'stock_transfer_id',
        'product_id',
        'quantity',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<StockTransferModel, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransferModel::class, 'stock_transfer_id');
    }

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    /**
     * Scope par transfert
     * 
     * @param \Illuminate\Database\Eloquent\Builder<StockTransferItemModel> $query
     * @return \Illuminate\Database\Eloquent\Builder<StockTransferItemModel>
     */
    public function scopeByTransfer($query, string $transferId)
    {
        return $query->where('stock_transfer_id', $transferId);
    }
}
