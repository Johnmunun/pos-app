<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent pour les items d'inventaire - Module Quincaillerie
 * 
 * @property string $id
 * @property string $inventory_id
 * @property string $product_id
 * @property float $system_quantity
 * @property float|null $counted_quantity
 * @property float $difference
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read InventoryModel|null $inventory
 * @property-read ProductModel|null $product
 */
class InventoryItemModel extends Model
{
    protected $table = 'quincaillerie_inventory_items';

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
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    /**
     * Relation avec l'inventaire
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(InventoryModel::class, 'inventory_id', 'id');
    }

    /**
     * Relation avec le produit
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'id');
    }

    /**
     * Scope par inventaire
     */
    public function scopeByInventory($query, string $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    /**
     * Scope avec écart
     */
    public function scopeWithDifference($query)
    {
        return $query->whereRaw('ABS(difference) > 0.01');
    }
}
