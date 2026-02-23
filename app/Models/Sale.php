<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'depot_id',
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
        'currency',
        'exchange_rate_snapshot',
        'notes',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'exchange_rate_snapshot' => 'array',
        'sold_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function items()
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