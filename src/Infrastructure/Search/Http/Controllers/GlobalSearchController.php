<?php

namespace Src\Infrastructure\Search\Http\Controllers;

use Src\Application\GlobalSearch\UseCases\SearchGlobalUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller GlobalSearchController
 *
 * Point d'entrée HTTP pour la recherche globale.
 * Utilise le Use Case pour la logique métier.
 *
 * @package Infrastructure\Search\Http\Controllers
 */
class GlobalSearchController
{
    public function __construct(
        private readonly SearchGlobalUseCase $searchUseCase
    ) {
    }

    /**
     * Recherche globale
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'results' => [],
            ]);
        }

        // Récupérer les permissions de l'utilisateur
        $permissions = $user->permissionCodes();
        $isRoot = $user->isRoot();

        // Exécuter le use case
        $results = $this->searchUseCase->execute(
            searchTerm: $request->input('q'),
            userPermissions: $permissions,
            isRootUser: $isRoot
        );

        return response()->json([
            'results' => $results,
        ]);
    }
}
