<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model Eloquent: Tenant
 *
 * Représentation de la table 'tenants' en base de données.
 * Ce model implémente le contrat TenantRepository du domain.
 *
 * Responsabilité:
 * - Mapper les données DB ↔ Entity Tenant
 * - Persister les données
 * - Fournir les méthodes de requête
 *
 * Ce model est dans l'INFRASTRUCTURE (pas dans le domain).
 * Le domain ne dépend pas de Laravel, c'est le contraire.
 *
 * Relations:
 * - Un tenant a plusieurs utilisateurs
 * - Un tenant a plusieurs boutiques (shops)
 * - Un tenant a plusieurs permissions (rôles)
 */
class Tenant extends Model
{
    use HasFactory;

    /**
     * Table associée au model
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * Les attributs qui peuvent être remplis en masse (mass-fillable)
     *
     * ⚠️ ATTENTION: Ne jamais permettre la modification de 'code' en mass-fillable
     * Le code est immutable dans le domain et ne doit pas être changeable
     *
     * @var array
     */
    protected $fillable = [
        'code',      // Code unique du tenant (immutable mais initialisé à la création)
        'name',      // Nom commercial
        'slug',      // Slug unique pour URLs
        'email',     // Email de contact
        'is_active', // État d'activation
    ];

    /**
     * Les attributs qui doivent être castés à des types natifs
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les attributs qui doivent être cachés pour la sérialisation
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Scope: Récupérer uniquement les tenants actifs
     *
     * Utilisé pour les listes publiques
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Récupérer uniquement les tenants inactifs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Trouver un tenant par son code unique
     *
     * @param string $code
     * @return self|null
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Trouver un tenant par son email
     *
     * @param string $email
     * @return self|null
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }

    /**
     * Vérifier si un code existe
     *
     * @param string $code
     * @param int|null $excludeId Exclure un ID spécifique (pour les mises à jour)
     * @return bool
     */
    public static function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = static::where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Vérifier si un email existe
     *
     * @param string $email
     * @param int|null $excludeId Exclure un ID spécifique
     * @return bool
     */
    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = static::where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Relations (à implémenter avec les autres domains)

    /**
     * Un tenant a plusieurs utilisateurs
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    /**
     * Un tenant a plusieurs boutiques
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function shops()
    // {
    //     return $this->hasMany(Shop::class);
    // }

    /**
     * Un tenant a plusieurs rôles
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function roles()
    // {
    //     return $this->hasMany(Role::class);
    // }
}
