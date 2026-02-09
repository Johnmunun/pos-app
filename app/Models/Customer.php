<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Eloquent: Customer
 *
 * Représentation de la table 'customers'.
 */
class Customer extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'customers';

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
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'credit_limit' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relations
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}