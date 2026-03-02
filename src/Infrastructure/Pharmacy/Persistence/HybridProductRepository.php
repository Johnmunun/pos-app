<?php

namespace Src\Infrastructure\Pharmacy\Persistence;

use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface as QuincaillerieProductRepositoryInterface;
use Src\Infrastructure\Pharmacy\Adapters\QuincaillerieProductRepositoryAdapter;

/**
 * Repository hybride pour les produits.
 *
 * - Pour le module Pharmacy : délègue entièrement à EloquentProductRepository.
 * - Pour le module Hardware : permet à findById() de retrouver aussi les produits Quincaillerie
 *   via un adapter, afin que les use cases (ventes) puissent simplement vérifier l'existence
 *   d'un produit Hardware sans changer toute la couche domaine.
 */
class HybridProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EloquentProductRepository $pharmacyRepository,
        private ?QuincaillerieProductRepositoryInterface $hardwareRepository = null,
    ) {
    }

    public function save(Product $product): void
    {
        // Sauvegarde uniquement via le repository Pharmacy existant
        $this->pharmacyRepository->save($product);
    }

    public function findById(string $id): ?Product
    {
        // 1) Essayer d'abord dans Pharmacy (comportement existant)
        $product = $this->pharmacyRepository->findById($id);
        if ($product !== null) {
            return $product;
        }

        // 2) Fallback pour Hardware : essayer dans Quincaillerie si disponible
        if ($this->hardwareRepository !== null) {
            $adapter = new QuincaillerieProductRepositoryAdapter($this->hardwareRepository);
            return $adapter->findById($id);
        }

        return null;
    }

    public function findByCode(ProductCode $code, string $shopId): ?Product
    {
        return $this->pharmacyRepository->findByCode($code, $shopId);
    }

    public function findByShop(string $shopId, array $filters = []): array
    {
        return $this->pharmacyRepository->findByShop($shopId, $filters);
    }

    public function findByCategory(string $categoryId, array $filters = []): array
    {
        return $this->pharmacyRepository->findByCategory($categoryId, $filters);
    }

    public function search(string $shopId, string $query, array $filters = []): array
    {
        return $this->pharmacyRepository->search($shopId, $query, $filters);
    }

    public function delete(string $id): void
    {
        $this->pharmacyRepository->delete($id);
    }

    public function update(Product $product): void
    {
        $this->pharmacyRepository->update($product);
    }

    public function existsByCode(string $code, ?string $excludeId = null): bool
    {
        return $this->pharmacyRepository->existsByCode($code, $excludeId);
    }

    public function getLowStockProducts(string $shopId, int $threshold = 10): array
    {
        return $this->pharmacyRepository->getLowStockProducts($shopId, $threshold);
    }

    public function getExpiredProducts(string $shopId): array
    {
        return $this->pharmacyRepository->getExpiredProducts($shopId);
    }

    public function getExpiringSoon(string $shopId, int $days = 30): array
    {
        return $this->pharmacyRepository->getExpiringSoon($shopId, $days);
    }

    public function findByType(string $shopId, string $type): array
    {
        return $this->pharmacyRepository->findByType($shopId, $type);
    }
}

