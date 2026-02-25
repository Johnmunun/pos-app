<?php

namespace Src\Infrastructure\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $shop_id
 * @property int|null $depot_id
 * @property float $amount
 * @property string $currency
 * @property string $category
 * @property string $description
 * @property string|null $supplier_id
 * @property string|null $attachment_path
 * @property string $status
 * @property int $created_by
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ExpenseModel extends Model
{
    protected $table = 'finance_expenses';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'shop_id',
        'depot_id',
        'amount',
        'currency',
        'category',
        'description',
        'supplier_id',
        'attachment_path',
        'status',
        'created_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
}
