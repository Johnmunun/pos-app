<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'credit_limit',
        'total_spent',
        'total_orders',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'credit_limit' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

