<?php

namespace Domains\User\ValueObjects;

/**
 * Value Object Password
 *
 * Représente le mot de passe d'un utilisateur.
 * Valide et immuable (jamais stocké en clair).
 *
 * Auto-validée au construction:
 * - Minumum 8 caractères
 * - Au moins une majuscule
 * - Au moins une minuscule
 * - Au moins un chiffre
 */
final class Password
{
    private readonly string $hashedValue;

    /**
     * Créer un mot de passe depuis une chaîne en clair
     *
     * @param string $plainPassword Le mot de passe en clair à valider
     * @throws \InvalidArgumentException Si le mot de passe ne respecte pas les critères
     */
    public function __construct(string $plainPassword)
    {
        // Valider la force du mot de passe
        $this->validatePassword($plainPassword);

        // Hacher le mot de passe
        // Utiliser bcrypt (via password_hash de PHP)
        $this->hashedValue = password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    /**
     * Factory: Créer un password à partir d'un hash existant
     *
     * Utilisé lors de l'hydratation depuis la DB
     *
     * @param string $hash Le hash bcrypt existant
     * @return self
     */
    public static function fromHash(string $hash): self
    {
        // Créer une instance sans passer par le constructeur normal
        // Utiliser ReflectionClass pour contourner la validation
        $reflection = new \ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        
        // Assigner directement le hash au propriété readonly
        $property = $reflection->getProperty('hashedValue');
        $property->setAccessible(true);
        $property->setValue($instance, $hash);
        
        return $instance;
    }

    /**
     * Obtenir le hash du mot de passe
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hashedValue;
    }

    /**
     * Vérifier si une chaîne en clair correspond à ce mot de passe
     *
     * @param string $plainPassword La chaîne à vérifier
     * @return bool
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    /**
     * Vérifier si le hash a besoin d'être rehashé
     *
     * Useful pour les mises à jour de sécurité
     *
     * @return bool
     */
    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_BCRYPT);
    }

    /**
     * Valider la force du mot de passe
     *
     * Critères:
     * - Minimum 8 caractères
     * - Au moins une majuscule
     * - Au moins une minuscule
     * - Au moins un chiffre
     *
     * @param string $password
     * @throws \InvalidArgumentException
     */
    private function validatePassword(string $password): void
    {
        // Longueur minimale
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException(
                'Password must be at least 8 characters long'
            );
        }

        // Au moins une majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain at least one uppercase letter'
            );
        }

        // Au moins une minuscule
        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain at least one lowercase letter'
            );
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain at least one number'
            );
        }
    }
}
