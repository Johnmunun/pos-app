<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for pharmacy_product_batches table.
 * 
 * @property string $id
 * @property string $shop_id
 * @property string $product_id
 * @property string $batch_number
 * @property int $quantity
 * @property \Carbon\Carbon $expiration_date
 * @property string|null $purchase_order_id
 * @property string|null $purchase_order_line_id
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read ProductModel|null $product
 * @property-read PurchaseOrderModel|null $purchaseOrder
 * 
 * @method static Builder|ProductBatchModel query()
 * @method static ProductBatchModel|null find(string $id)
 * @method static ProductBatchModel create(array $attributes)
 * @method static Builder|ProductBatchModel where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder|ProductBatchModel whereIn(string $column, array $values)
 * @method static Builder|ProductBatchModel active()
 * @method static Builder|ProductBatchModel byShop(string $shopId)
 * @method static Builder|ProductBatchModel byProduct(string $productId)
 * @method static Builder|ProductBatchModel expired(?DateTimeImmutable $asOf = null)
 * @method static Builder|ProductBatchModel expiring(int $days, ?DateTimeImmutable $asOf = null)
 * @method static Builder|ProductBatchModel withStock()
 */
class ProductBatchModel extends Model
{
    protected $table = 'pharmacy_product_batches';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'product_id',
        'batch_number',
        'quantity',
        'expiration_date',
        'purchase_order_id',
        'purchase_order_line_id',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'is_active' => 'boolean',
        'expiration_date' => 'date',
        'depot_id' => 'integer',
    ];

    /**
     * Product relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    /**
     * Purchase order relationship.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }

    /**
     * Scope: Active batches only.
     * 
     * @param Builder<ProductBatchModel> $query
     * @return Builder<ProductBatchModel>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by shop.
     * 
     * @param Builder<ProductBatchModel> $query
     * @param string $shopId
     * @return Builder<ProductBatchModel>
     */
    public function scopeByShop(Builder $query, string $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by product.
     * 
     * @param Builder<ProductBatchModel> $query
     * @param string $productId
     * @return Builder<ProductBatchModel>
     */
    public function scopeByProduct(Builder $query, string $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Expired batches.
     * 
     * @param Builder<ProductBatchModel> $query
     * @param DateTimeImmutable|null $asOf
     * @return Builder<ProductBatchModel>
     */
    public function scopeExpired(Builder $query, ?DateTimeImmutable $asOf = null): Builder
    {
        $date = $asOf ?? new DateTimeImmutable();
        return $query->where('expiration_date', '<', $date->format('Y-m-d'));
    }

    /**
     * Scope: Expiring within X days (not yet expired).
     * 
     * @param Builder<ProductBatchModel> $query
     * @param int $days
     * @param DateTimeImmutable|null $asOf
     * @return Builder<ProductBatchModel>
     */
    public function scopeExpiring(Builder $query, int $days, ?DateTimeImmutable $asOf = null): Builder
    {
        $now = $asOf ?? new DateTimeImmutable();
        $threshold = $now->modify("+{$days} days");
        
        return $query
            ->where('expiration_date', '>=', $now->format('Y-m-d'))
            ->where('expiration_date', '<=', $threshold->format('Y-m-d'));
    }

    /**
     * Scope: Batches with available stock.
     * 
     * @param Builder<ProductBatchModel> $query
     * @return Builder<ProductBatchModel>
     */
    public function scopeWithStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired(?DateTimeImmutable $asOf = null): bool
    {
        $date = $asOf ?? new DateTimeImmutable();
        $expirationDate = $this->expiration_date;
        
        return $expirationDate->lessThan($date);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): int
    {
        $now = \Carbon\Carbon::now();
        $expirationDate = $this->expiration_date;
        
        return (int) $now->diffInDays($expirationDate, false);
    }

    /**
     * Get expiration status.
     * 
     * @return string 'expired' | 'expiring_soon' | 'ok'
     */
    public function getExpirationStatus(): string
    {
        $days = $this->getDaysUntilExpiration();
        
        if ($days < 0) {
            return 'expired';
        }
        
        if ($days <= 30) {
            return 'expiring_soon';
        }
        
        return 'ok';
    }
}
