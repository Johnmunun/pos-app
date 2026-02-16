<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent pour les items d'inventaire
 * 
 * @property string $id
 * @property string $inventory_id
 * @property string $product_id
 * @property int $system_quantity
 * @property int|null $counted_quantity
 * @property int $difference
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read InventoryModel|null $inventory
 * @property-read ProductModel|null $product
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @method static static|null find(string $id)
 * @method static static findOrFail(string $id)
 * @method static \Illuminate\Database\Eloquent\Builder|static where(string $column, mixed $operator = null, mixed $value = null)
 * @method static static updateOrCreate(array $attributes, array $values = [])
 */
class InventoryItemModel extends Model
{
    protected $table = 'pharmacy_inventory_items';

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
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'difference' => 'integer',
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
        return $query->where('difference', '!=', 0);
    }
}
