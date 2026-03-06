<?php

declare(strict_types=1);

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent pour les items d'inventaire - Module Global Commerce
 *
 * @property string $id
 * @property string $inventory_id
 * @property string $product_id
 * @property float $system_quantity
 * @property float|null $counted_quantity
 * @property float $difference
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read GcInventoryModel|null $inventory
 * @property-read ProductModel|null $product
 */
class GcInventoryItemModel extends Model
{
    protected $table = 'gc_inventory_items';

    protected $fillable = [
        'id',
        'inventory_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'difference',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'difference' => 'decimal:4',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(GcInventoryModel::class, 'inventory_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'id');
    }

    public function scopeByInventory($query, string $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }
}
