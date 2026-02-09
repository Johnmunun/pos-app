<?php

namespace Src\Application\GlobalSearch\Repositories;

use Domains\GlobalSearch\ValueObjects\GlobalSearchItem;

/**
 * Interface GlobalSearchRepositoryInterface
 *
 * Contrat pour récupérer les items de recherche.
 * L'implémentation sera dans l'Infrastructure.
 *
 * @package Application\GlobalSearch\Repositories
 */
interface GlobalSearchRepositoryInterface
{
    /**
     * Récupère tous les items recherchables du système
     *
     * @return array<GlobalSearchItem>
     */
    public function getAllSearchableItems(): array;
}
