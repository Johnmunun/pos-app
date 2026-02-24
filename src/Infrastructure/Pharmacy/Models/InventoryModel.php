<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Models;

use App\Models\Depot;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent pour les inventaires
 *
 * @property string $id
 * @property string $shop_id
 * @property int|null $depot_id
 * @property string $reference
 * @property string $status
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $validated_at
 * @property int $created_by
 * @property int|null $validated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Depot|null $depot
 * @property-read User|null $creator
 * @property-read User|null $validator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InventoryItemModel> $items
 * @property-read int|null $items_count
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @method static static|null find(string $id)
 * @method static static findOrFail(string $id)
 * @method static \Illuminate\Database\Eloquent\Builder|static where(string $column, mixed $operator = null, mixed $value = null)
 * @method static static updateOrCreate(array $attributes, array $values = [])
 */
class InventoryModel extends Model
{
    protected $table = 'pharmacy_inventories';

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'reference',
        'status',
        'started_at',
        'validated_at',
        'created_by',
        'validated_by',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'depot_id' => 'integer',
        'started_at' => 'datetime',
        'validated_at' => 'datetime',
        'created_by' => 'integer',
        'validated_by' => 'integer',
    ];

    /**
     * Relation avec le dépôt
     */
    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    /**
     * Relation avec les items
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItemModel::class, 'inventory_id', 'id');
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Relation avec l'utilisateur validateur
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by', 'id');
    }

    /**
     * Scope par boutique
     */
    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope par dépôt
     */
    public function scopeByDepot($query, ?int $depotId)
    {
        if ($depotId === null) {
            return $query->whereNull('depot_id');
        }
        return $query->where('depot_id', $depotId);
    }

    /**
     * Scope par statut
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
