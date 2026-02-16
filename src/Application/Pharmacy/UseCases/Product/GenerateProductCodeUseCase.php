<?php

namespace Src\Application\Pharmacy\UseCases\Product;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;

/**
 * Génère un code produit unique et valide à partir du nom du produit.
 * Respecte les règles métier de ProductCode et vérifie l'unicité via le Repository.
 */
class GenerateProductCodeUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @param string $productName Nom du produit fourni par l'utilisateur
     */
    public function execute(string $productName): ProductCode
    {
        // Normaliser le nom pour créer un préfixe significatif
        $normalizedName = strtoupper(trim($productName));
        $normalizedName = preg_replace('/[^A-Z0-9]/', '', $normalizedName ?? '');

        if ($normalizedName === '' || $normalizedName === null) {
            $normalizedName = 'PROD';
        }

        // 3 premières lettres/chiffres pour le préfixe
        $prefix = substr($normalizedName, 0, 3);

        // Boucle jusqu'à obtenir un code valide ET unique
        do {
            // 4 chiffres aléatoires → format du type ABC-1234 (longueur 8, conforme à ProductCode)
            $randomNumber = random_int(1000, 9999);
            $candidate = $prefix . '-' . $randomNumber;

            try {
                $productCode = new ProductCode($candidate);
            } catch (\InvalidArgumentException) {
                // Si pour une raison quelconque le code ne respecte pas les règles,
                // on réessaie avec un autre nombre.
                continue;
            }

            // Vérifier l'unicité dans la base
            $exists = $this->productRepository->existsByCode((string) $productCode);
        } while ($exists);

        return $productCode;
    }
}

