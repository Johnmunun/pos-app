<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $table = 'product_batches';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'batch_number',
        'manufacturing_date',
        'expiration_date',
        'quantity',
        'purchase_price',
        'is_active',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiration_date' => 'date',
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if batch is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isFuture() 
            && $this->expiration_date->lte(now()->addDays($days));
    }
}
