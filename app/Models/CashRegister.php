<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $table = 'cash_registers';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'name',
        'code',
        'description',
        'initial_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class, 'cash_register_id');
    }

    /** Session actuellement ouverte pour cette caisse, s'il y en a une. */
    public function openSession(): ?CashRegisterSession
    {
        return $this->sessions()->where('status', 'open')->first();
    }
}
