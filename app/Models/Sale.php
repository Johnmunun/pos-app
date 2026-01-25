<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'cash_register_id',
        'cash_register_session_id',
        'sale_number',
        'customer_id',
        'seller_id',
        'status',
        'payment_type',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Generate unique sale number
     *
     * @param int $tenantId
     * @return string
     */
    public static function generateSaleNumber(int $tenantId): string
    {
        $prefix = 'SALE';
        $date = now()->format('Ymd');
        /** @var self|null $lastSale */
        $lastSale = self::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastSale ? ((int) substr($lastSale->sale_number, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}

