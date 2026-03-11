<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $city
 * @property string $country
 * @property bool $status
 * @property string|null $logo
 * @property string|null $legal_form
 * @property string|null $registration_number
 * @property string|null $currency_code
 * @property string|null $timezone
 * @property string|null $locale
 */
class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'status',
        'logo',
        'sector',
        'slug',
        'business_type',
        'legal_form',
        'registration_number',
        'tax_id',
        'currency_code',
        'timezone',
        'locale',
    ];

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Get the users for the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the shops for the tenant.
     */
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    /**
     * Get the depots for the tenant.
     */
    public function depots()
    {
        return $this->hasMany(Depot::class);
    }

    /**
     * Trouve un tenant par code unique.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Trouve un tenant par email.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }

    /**
     * Scope pour les tenants actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Vérifie si un code existe déjà.
     */
    public static function codeExists(string $code): bool
    {
        return static::where('code', $code)->exists();
    }

    /**
     * Vérifie si un email existe déjà.
     */
    public static function emailExists(string $email): bool
    {
        return static::where('email', $email)->exists();
    }
}