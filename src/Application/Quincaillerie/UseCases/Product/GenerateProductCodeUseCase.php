<?php

namespace Src\Application\Quincaillerie\UseCases\Product;

use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\ValueObjects\ProductCode;

/**
 * Génération d'un code produit unique - Module Quincaillerie.
 */
class GenerateProductCodeUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    /** @param string|null $shopId Si fourni, vérifie l'unicité dans ce shop uniquement */
    public function execute(string $name, ?string $shopId = null): ProductCode
    {
        // Normaliser le nom pour créer un préfixe significatif (3 caractères max)
        $normalized = strtoupper(trim($name));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);
        if ($normalized === '') {
            $normalized = 'HWQ'; // fallback générique Hardware Quincaillerie
        }
        $prefix = substr($normalized, 0, 3);

        // Génère des codes du type ABC-1234, faciles à lire comme de "vrais" codes produit
        $productCode = null;
        $exists = true;

        do {
            $randomNumber = random_int(1000, 9999);
            $candidate = $prefix . '-' . $randomNumber;

            try {
                $productCode = new ProductCode($candidate);
            } catch (\InvalidArgumentException) {
                // Si pour une raison quelconque le code ne respecte pas les règles du ValueObject,
                // on réessaie avec un autre nombre.
                continue;
            }

            $exists = $this->productRepository->existsByCode($productCode->getValue(), null, $shopId);
        } while ($exists);

        \assert($productCode instanceof ProductCode);

        return $productCode;
    }
}
