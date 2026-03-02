<?php

namespace Src\Infrastructure\Pharmacy\Adapters;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ProductCode;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface as QuincaillerieProductRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as QuincaillerieProductModel;

/**
 * Adapter qui permet d'utiliser le repository Quincaillerie comme un repository Pharmacy
 * pour les use cases qui nécessitent uniquement la vérification d'existence du produit.
 */
class QuincaillerieProductRepositoryAdapter implements ProductRepositoryInterface
{
    public function __construct(
        private QuincaillerieProductRepositoryInterface $quincaillerieRepository
    ) {}

    public function save(Product $product): void
    {
        throw new \BadMethodCallException('Save not supported for Quincaillerie adapter');
    }

    public function findById(string $id): ?Product
    {
        // Vérifier dans Quincaillerie
        $quincaillerieProduct = $this->quincaillerieRepository->findById($id);
        if ($quincaillerieProduct === null) {
            return null;
        }

        // Convertir l'entité Quincaillerie en entité Pharmacy pour le use case
        // On crée une entité Pharmacy minimale juste pour la vérification d'existence
        // Le use case n'utilise que findById pour vérifier l'existence, pas les autres propriétés
        $code = new ProductCode($quincaillerieProduct->getCode()->getValue());
        $price = $quincaillerieProduct->getPrice();
        $stock = $quincaillerieProduct->getStock();

        return new Product(
            $quincaillerieProduct->getId(),
            $quincaillerieProduct->getShopId(),
            $code,
            $quincaillerieProduct->getName(),
            $quincaillerieProduct->getDescription(),
            new \Src\Domain\Pharmacy\ValueObjects\MedicineType('DEVICE'), // Type valide pour produits Hardware
            null,
            $price,
            $stock,
            new \Src\Domain\Pharmacy\ValueObjects\TypeUnite(\Src\Domain\Pharmacy\ValueObjects\TypeUnite::UNITE),
            1,
            true,
            $quincaillerieProduct->getCategoryId(),
            false,
            $quincaillerieProduct->getMinimumStock()
        );
    }

    public function findByCode(ProductCode $code, string $shopId): ?Product
    {
        throw new \BadMethodCallException('findByCode not supported for Quincaillerie adapter');
    }

    public function findByShop(string $shopId, array $filters = []): array
    {
        throw new \BadMethodCallException('findByShop not supported for Quincaillerie adapter');
    }

    public function findByCategory(string $categoryId, array $filters = []): array
    {
        throw new \BadMethodCallException('findByCategory not supported for Quincaillerie adapter');
    }

    public function search(string $shopId, string $query, array $filters = []): array
    {
        throw new \BadMethodCallException('search not supported for Quincaillerie adapter');
    }

    public function delete(string $id): void
    {
        throw new \BadMethodCallException('delete not supported for Quincaillerie adapter');
    }

    public function update(Product $product): void
    {
        // Récupérer le produit Quincaillerie existant
        $quincaillerieProduct = $this->quincaillerieRepository->findById($product->getId());
        if ($quincaillerieProduct === null) {
            throw new \InvalidArgumentException('Product not found in Quincaillerie repository');
        }

        // Mettre à jour uniquement le stock (c'est ce que HardwareUpdateStockUseCase modifie)
        // L'entité Pharmacy Product a déjà été modifiée avec addStock() ou decreaseStock()
        // On synchronise le stock de l'entité Pharmacy vers l'entité Quincaillerie
        $newStock = $product->getStock();
        $currentStock = $quincaillerieProduct->getStock();
        
        if (abs($newStock->getValue() - $currentStock->getValue()) > 0.0001) {
            // Mettre à jour le stock directement
            $quincaillerieProduct->updateStock(new \Src\Shared\ValueObjects\Quantity($newStock->getValue()));
            
            // Sauvegarder via le repository Quincaillerie
            $this->quincaillerieRepository->update($quincaillerieProduct);
        }
    }

    public function existsByCode(string $code, ?string $excludeId = null): bool
    {
        throw new \BadMethodCallException('existsByCode not supported for Quincaillerie adapter');
    }

    public function getLowStockProducts(string $shopId, int $threshold = 10): array
    {
        throw new \BadMethodCallException('getLowStockProducts not supported for Quincaillerie adapter');
    }

    public function getExpiredProducts(string $shopId): array
    {
        throw new \BadMethodCallException('getExpiredProducts not supported for Quincaillerie adapter');
    }

    public function getExpiringSoon(string $shopId, int $days = 30): array
    {
        throw new \BadMethodCallException('getExpiringSoon not supported for Quincaillerie adapter');
    }

    public function findByType(string $shopId, string $type): array
    {
        throw new \BadMethodCallException('findByType not supported for Quincaillerie adapter');
    }
}
