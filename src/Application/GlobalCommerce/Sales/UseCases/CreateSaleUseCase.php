<?php

namespace Src\Application\GlobalCommerce\Sales\UseCases;

use Src\Application\GlobalCommerce\Sales\DTO\CreateSaleDTO;
use Src\Domain\GlobalCommerce\Sales\Entities\Sale;
use Src\Domain\GlobalCommerce\Sales\Repositories\SaleRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Shared\ValueObjects\Quantity;

final class CreateSaleUseCase
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(CreateSaleDTO $dto): Sale
    {
        if (empty($dto->lines)) {
            throw new \InvalidArgumentException('La vente doit contenir au moins une ligne.');
        }

        $currency = $dto->currency;
        $saleLines = [];
        $totalAmount = 0.0;

        foreach ($dto->lines as $line) {
            $productId = $line['product_id'];
            $quantity = (float) $line['quantity'];
            if ($quantity <= 0) {
                continue;
            }
            $product = $this->productRepository->findById($productId);
            if (!$product || $product->getShopId() !== $dto->shopId) {
                throw new \InvalidArgumentException("Produit invalide: {$productId}");
            }

            // Prix unitaire: si le POS envoie un prix "discuté" ou converti, on l'utilise.
            // Sinon fallback sur le prix de vente du produit.
            $hasExplicitUnitPrice = array_key_exists('unit_price', $line) && $line['unit_price'] !== null && $line['unit_price'] !== '';
            $unitPrice = $hasExplicitUnitPrice ? (float) $line['unit_price'] : $product->getSalePrice()->getAmount();

            // Vérifier la devise uniquement quand on utilise le prix produit (pas de prix explicite).
            // Quand le frontend envoie unit_price, il l'a déjà converti dans la devise sélectionnée.
            if (!$hasExplicitUnitPrice && $product->getSalePrice()->getCurrency() !== $currency) {
                throw new \InvalidArgumentException('Tous les produits doivent être dans la même devise.');
            }
            if ($unitPrice < 0) {
                throw new \InvalidArgumentException('Prix unitaire invalide.');
            }

            $discountPercent = 0.0;
            if (array_key_exists('discount_percent', $line) && $line['discount_percent'] !== null && $line['discount_percent'] !== '') {
                $discountPercent = (float) $line['discount_percent'];
                if ($discountPercent < 0 || $discountPercent > 100) {
                    throw new \InvalidArgumentException('Remise invalide (0-100%).');
                }
            }

            $lineTotal = $quantity * $unitPrice;
            $discountAmount = $discountPercent > 0 ? ($lineTotal * $discountPercent) / 100 : 0.0;
            $subtotal = round(max(0, $lineTotal - $discountAmount), 2);
            $totalAmount += $subtotal;
            $saleLines[] = [
                'product_id' => $productId,
                'product_name' => $product->getName(),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        if (empty($saleLines)) {
            throw new \InvalidArgumentException('La vente doit contenir au moins une ligne avec une quantité > 0.');
        }

        $sale = Sale::create(
            $dto->shopId,
            round($totalAmount, 2),
            $currency,
            $saleLines,
            $dto->customerName,
            $dto->notes,
            $dto->createdByUserId,
            $dto->isDraft
        );

        // Déduction du stock uniquement pour les ventes finalisées (pas les brouillons)
        if (!$dto->isDraft) {
            foreach ($dto->lines as $line) {
                $quantity = (float) $line['quantity'];
                if ($quantity <= 0) {
                    continue;
                }
                $product = $this->productRepository->findById($line['product_id']);
                if ($product && $product->getShopId() === $dto->shopId) {
                    $product->removeStock(new Quantity($quantity));
                    $this->productRepository->update($product);
                }
            }
        }

        $this->saleRepository->save($sale);
        return $sale;
    }
}
