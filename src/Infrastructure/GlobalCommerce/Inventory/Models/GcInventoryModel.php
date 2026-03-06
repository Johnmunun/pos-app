<?php

declare(strict_types=1);

namespace Src\Infrastructure\GlobalCommerce\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent pour les inventaires - Module Global Commerce
 *
 * @property string $id
 * @property int $shop_id
 * @property string $reference
 * @property string $status
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $validated_at
 * @property int $created_by
 * @property int|null $validated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User|null $creator
 * @property-read User|null $validator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GcInventoryItemModel> $items
 * @property-read int|null $items_count
 */
class GcInventoryModel extends Model
{
    protected $table = 'gc_inventories';

    protected $fillable = [
        'id',
        'shop_id',
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
        'shop_id' => 'integer',
        'started_at' => 'datetime',
        'validated_at' => 'datetime',
        'created_by' => 'integer',
        'validated_by' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GcInventoryItemModel::class, 'inventory_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by', 'id');
    }

    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
