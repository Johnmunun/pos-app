<?php

namespace Src\Infrastructure\Pharmacy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BatchModel extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'pharmacy_batches';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'depot_id',
        'product_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'initial_quantity',
        'supplier_id',
        'purchase_order_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'float',
        'initial_quantity' => 'float',
        'depot_id' => 'integer',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_id');
    }

    public function depot()
    {
        return $this->belongsTo(\App\Models\Depot::class, 'depot_id');
    }

    // Scopes
    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        $expiryDate = now()->addDays($days);
        return $query->where('expiry_date', '<=', $expiryDate)
                    ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('quantity', '<=', $threshold);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date < now();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date <= now()->addDays(30) && $this->expiry_date >= now();
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getStockPercentageAttribute(): float
    {
        if ($this->initial_quantity === 0) {
            return 0;
        }
        
        return ($this->quantity / $this->initial_quantity) * 100;
    }

    public function getConsumedQuantityAttribute(): int
    {
        return $this->initial_quantity - $this->quantity;
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        $warningDate = now()->addDays($days);
        return $this->expiry_date <= $warningDate && $this->expiry_date >= now();
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->quantity <= $threshold;
    }

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    public function consume(int $quantity): void
    {
        if ($this->quantity < $quantity) {
            throw new \InvalidArgumentException('Insufficient batch quantity');
        }
        
        $this->quantity -= $quantity;
        $this->save();
    }

    public function addStock(int $quantity): void
    {
        $this->quantity += $quantity;
        $this->initial_quantity += $quantity;
        $this->save();
    }
}