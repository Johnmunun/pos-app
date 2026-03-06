<?php

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $reference
 * @property int $from_shop_id
 * @property int $to_shop_id
 * @property string $status
 * @property int $created_by
 * @property int|null $validated_by
 * @property \Carbon\Carbon|null $validated_at
 * @property string|null $notes
 * @property-read Shop|null $fromShop
 * @property-read Shop|null $toShop
 * @property-read User|null $creator
 * @property-read User|null $validator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GcStockTransferItemModel> $items
 */
class GcStockTransferModel extends Model
{
    protected $table = 'gc_stock_transfers';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'reference',
        'from_shop_id',
        'to_shop_id',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'from_shop_id' => 'integer',
        'to_shop_id' => 'integer',
        'created_by' => 'integer',
        'validated_by' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function fromShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'from_shop_id');
    }

    public function toShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'to_shop_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GcStockTransferItemModel::class, 'stock_transfer_id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
