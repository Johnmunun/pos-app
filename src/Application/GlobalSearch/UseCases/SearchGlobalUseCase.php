<?php

namespace Src\Application\GlobalSearch\UseCases;

use Src\Application\GlobalSearch\Repositories\GlobalSearchRepositoryInterface;
use Src\Domains\GlobalSearch\ValueObjects\GlobalSearchItem;

/**
 * Use Case: SearchGlobalUseCase
 *
 * Responsabilités :
 * - Recevoir le terme de recherche
 * - Filtrer les résultats par permissions utilisateur
 * - Retourner une liste normalisée de résultats
 *
 * Règle ROOT : Si l'utilisateur est ROOT, retourner TOUS les résultats
 *
 * @package Application\GlobalSearch\UseCases
 */
class SearchGlobalUseCase
{
    public function __construct(
        private readonly GlobalSearchRepositoryInterface $searchRepository
    ) {
    }

    /**
     * Exécute la recherche globale
     *
     * @param string $searchTerm Terme de recherche
     * @param array $userPermissions Permissions de l'utilisateur connecté
     * @param bool $isRootUser Indique si l'utilisateur est ROOT
     * @return array Liste de GlobalSearchItem filtrés et groupés par module
     */
    public function execute(
        string $searchTerm,
        array $userPermissions,
        bool $isRootUser = false
    ): array {
        // Récupérer tous les items de recherche
        $allItems = $this->searchRepository->getAllSearchableItems();

        // Filtrer par terme de recherche
        $matchedItems = array_filter(
            $allItems,
            fn(GlobalSearchItem $item) => $item->matches($searchTerm)
        );

        // Filtrer par permissions (sauf si ROOT)
        if (!$isRootUser) {
            $matchedItems = array_filter(
                $matchedItems,
                function (GlobalSearchItem $item) use ($userPermissions) {
                    $requiredPermission = $item->getRequiredPermission();
                    
                    // Si aucune permission requise, accessible à tous
                    if ($requiredPermission === null) {
                        return true;
                    }

                    // Vérifier si l'utilisateur a la permission
                    return in_array($requiredPermission, $userPermissions, true);
                }
            );
        }

        // Grouper par module
        $grouped = [];
        foreach ($matchedItems as $item) {
            $module = $item->getModule();
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $item->toArray();
        }

        // Trier les modules par ordre alphabétique
        ksort($grouped);

        return $grouped;
    }
}
