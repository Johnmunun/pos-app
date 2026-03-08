<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Illuminate\Support\Facades\DB;
use Src\Application\Pharmacy\DTO\SaleLineDTO;
use Src\Domain\Pharmacy\Entities\SaleLine;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Use case : mettre à jour le panier (lignes de vente) d'une vente en statut DRAFT.
 *
 * Décision de simplification : on remplace l'ensemble des lignes par la liste fournie.
 */
class UpdateSaleLinesUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private SaleLineRepositoryInterface $saleLineRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * @param string $saleId
     * @param SaleLineDTO[] $lines
     */
    public function execute(string $saleId, array $lines): void
    {
        DB::transaction(function () use ($saleId, $lines): void {
            $sale = $this->saleRepository->findById($saleId);

            if (!$sale) {
                throw new \InvalidArgumentException('Sale not found');
            }

            if ($sale->getStatus() !== \Src\Domain\Pharmacy\Entities\Sale::STATUS_DRAFT) {
                throw new \LogicException('Only draft sales can be updated');
            }

            // On supprime les lignes existantes et on les remplace par les nouvelles
            $this->saleLineRepository->deleteBySale($saleId);

            $currency = $sale->getCurrency();
            $total = new Money(0, $currency);

            foreach ($lines as $lineDto) {
                // Vérifier que le produit existe
                $product = $this->productRepository->findById($lineDto->productId);
                
                // Si le produit n'est pas trouvé via le repository, vérifier directement dans la base
                // (peut arriver si le produit vient d'être créé ou si le repository filtre par dépôt)
                if (!$product) {
                    $productModel = null;
                    $productName = 'Inconnu';
                    
                    try {
                        // Pour Hardware, vérifier directement dans le modèle Eloquent
                        if (class_exists(\Src\Infrastructure\Quincaillerie\Models\ProductModel::class)) {
                            $productModel = \Src\Infrastructure\Quincaillerie\Models\ProductModel::find($lineDto->productId);
                            if ($productModel && $productModel->is_active) {
                                // Le produit existe et est actif, utiliser l'adapter pour le convertir
                                $adapter = new \Src\Infrastructure\Pharmacy\Adapters\QuincaillerieProductRepositoryAdapter(
                                    app(\Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface::class)
                                );
                                $product = $adapter->findById($lineDto->productId);
                                $productName = $productModel->name ?? 'Inconnu';
                            } else if ($productModel) {
                                $productName = $productModel->name ?? 'Inconnu';
                                throw new \InvalidArgumentException(
                                    sprintf(
                                        'Le produit "%s" (ID: %s) est désactivé.',
                                        $productName,
                                        $lineDto->productId
                                    )
                                );
                            }
                        }
                        
                        // Pour Pharmacy, essayer aussi
                        if (!$product && class_exists(\Src\Infrastructure\Pharmacy\Models\ProductModel::class)) {
                            $productModel = \Src\Infrastructure\Pharmacy\Models\ProductModel::find($lineDto->productId);
                            if ($productModel && $productModel->is_active) {
                                // Utiliser le repository Pharmacy directement
                                $pharmacyRepo = app(\Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface::class);
                                $product = $pharmacyRepo->findById($lineDto->productId);
                                $productName = $productModel->name ?? 'Inconnu';
                            } else if ($productModel) {
                                $productName = $productModel->name ?? 'Inconnu';
                                throw new \InvalidArgumentException(
                                    sprintf(
                                        'Le produit "%s" (ID: %s) est désactivé.',
                                        $productName,
                                        $lineDto->productId
                                    )
                                );
                            }
                        }
                    } catch (\InvalidArgumentException $e) {
                        // Relancer les InvalidArgumentException
                        throw $e;
                    } catch (\Throwable $e) {
                        // Ignorer les autres erreurs
                    }
                    
                    // Si le produit n'a toujours pas été trouvé
                    if (!$product) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Produit introuvable: "%s" (ID: %s). Le produit a peut-être été supprimé ou n\'existe pas.',
                                $productName,
                                $lineDto->productId
                            )
                        );
                    }
                }

                // Règle métier : produit non divisible → quantité entière obligatoire
                if (!$product->estDivisible()) {
                    $q = $lineDto->quantity;
                    if (abs($q - (int) $q) > 0.0001) {
                        throw new \InvalidArgumentException(
                            'Le produit "' . $product->getName() . '" ne se vend pas en fraction. Quantité entière requise.'
                        );
                    }
                }

                $quantity = new Quantity($lineDto->quantity);
                $unitPrice = new Money($lineDto->unitPrice, $currency);

                $line = SaleLine::create(
                    $saleId,
                    $lineDto->productId,
                    $quantity,
                    $unitPrice,
                    $lineDto->discountPercent
                );

                $this->saleLineRepository->save($line);
                $total = $total->add($line->getLineTotal());
            }

            // On laisse le paidAmount/balance gérés à la finalisation
            $sale->updateTotals($total, new Money($sale->getPaidAmount()->getAmount(), $currency));
            $this->saleRepository->save($sale);
        });
    }
}

