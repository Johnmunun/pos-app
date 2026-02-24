<?php

namespace Src\Infrastructure\Pharmacy\Models;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $pharmacy_id
 * @property string $reference
 * @property int $from_shop_id
 * @property int $to_shop_id
 * @property string $status
 * @property int $created_by
 * @property int|null $validated_by
 * @property string|null $validated_at
 * @property string|null $notes
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read Shop|null $fromShop
 * @property-read Shop|null $toShop
 * @property-read User|null $creator
 * @property-read User|null $validator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockTransferItemModel> $items
 */
class StockTransferModel extends Model
{
    protected $table = 'pharmacy_stock_transfers';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'pharmacy_id',
        'reference',
        'from_shop_id',
        'from_depot_id',
        'to_shop_id',
        'to_depot_id',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'from_shop_id' => 'integer',
        'from_depot_id' => 'integer',
        'to_shop_id' => 'integer',
        'to_depot_id' => 'integer',
        'created_by' => 'integer',
        'validated_by' => 'integer',
        'validated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function fromShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'from_shop_id');
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function toShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'to_shop_id');
    }

    public function fromDepot(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Depot::class, 'from_depot_id');
    }

    public function toDepot(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Depot::class, 'to_depot_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return HasMany<StockTransferItemModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItemModel::class, 'stock_transfer_id');
    }

    /**
     * Scope par pharmacie
     * 
     * @param \Illuminate\Database\Eloquent\Builder<StockTransferModel> $query
     * @return \Illuminate\Database\Eloquent\Builder<StockTransferModel>
     */
    public function scopeByPharmacy($query, string $pharmacyId)
    {
        return $query->where('pharmacy_id', $pharmacyId);
    }

    /**
     * Scope par statut
     * 
     * @param \Illuminate\Database\Eloquent\Builder<StockTransferModel> $query
     * @return \Illuminate\Database\Eloquent\Builder<StockTransferModel>
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
