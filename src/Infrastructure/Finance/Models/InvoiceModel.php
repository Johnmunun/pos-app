<?php

namespace Src\Infrastructure\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $shop_id
 * @property string $number
 * @property string $source_type
 * @property string $source_id
 * @property float $total_amount
 * @property float $paid_amount
 * @property string $currency
 * @property string $status
 * @property \Carbon\Carbon $issued_at
 * @property \Carbon\Carbon|null $validated_at
 * @property \Carbon\Carbon|null $paid_at
 */
class InvoiceModel extends Model
{
    protected $table = 'finance_invoices';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'tenant_id', 'shop_id', 'number', 'source_type', 'source_id',
        'total_amount', 'paid_amount', 'currency', 'status',
        'issued_at', 'validated_at', 'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'validated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
}
