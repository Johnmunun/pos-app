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
        $base = preg_replace('/[^A-Z0-9]/i', '', $name);
        $base = strtoupper(substr($base, 0, 6)) ?: 'Q';
        $code = $base;
        $counter = 1;
        while ($this->productRepository->existsByCode($code, null, $shopId)) {
            $code = $base . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }
        return new ProductCode($code);
    }
}
