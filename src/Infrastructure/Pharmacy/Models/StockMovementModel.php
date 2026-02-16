<?php

namespace Src\Infrastructure\Pharmacy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $shop_id
 * @property string $product_id
 * @property string $type
 * @property int $quantity
 * @property string|null $reference
 * @property int $created_by
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read ProductModel|null $product
 * @property-read User|null $creator
 */
class StockMovementModel extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<StockMovementModel>> */
    use HasFactory;

    protected $table = 'pharmacy_stock_movements';

    protected $fillable = [
        'id',
        'shop_id',
        'product_id',
        'type',
        'quantity',
        'reference',
        'created_by',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

