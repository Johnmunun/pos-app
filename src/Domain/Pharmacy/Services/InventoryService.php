<?php

namespace Src\Domain\Pharmacy\Services;

use Src\Domain\Pharmacy\Entities\Product;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use Src\Shared\ValueObjects\Quantity;
use DateTimeImmutable;

class InventoryService
{
    /**
     * Check if product stock is below minimum level
     */
    public function isLowStock(Product $product): bool
    {
        return $product->getStock()->getValue() <= $product->getMinimumStock()->getValue();
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(Product $product): bool
    {
        return $product->getStock()->getValue() <= 0;
    }

    /**
     * Calculate stock status level
     */
    public function getStockStatus(Product $product): string
    {
        $current = $product->getStock()->getValue();
        $minimum = $product->getMinimumStock()->getValue();
        
        if ($current <= 0) {
            return 'out_of_stock';
        }
        
        if ($current <= $minimum) {
            return 'low_stock';
        }
        
        if ($current <= ($minimum * 2)) {
            return 'medium_stock';
        }
        
        return 'good_stock';
    }

    /**
     * Calculate reorder quantity needed
     */
    public function calculateReorderQuantity(Product $product): int
    {
        $current = $product->getStock()->getValue();
        $minimum = $product->getMinimumStock()->getValue();
        
        if ($current >= $minimum) {
            return 0;
        }
        
        // Reorder to double the minimum stock
        return ($minimum * 2) - $current;
    }

    /**
     * Validate stock adjustment
     */
    public function validateStockAdjustment(Product $product, int $newQuantity): void
    {
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative');
        }
        
        if ($newQuantity > 999999) {
            throw new \InvalidArgumentException('Stock quantity cannot exceed 999,999');
        }
    }

    /**
     * Check if adjustment would create negative stock
     */
    public function wouldCreateNegativeStock(Product $product, int $adjustment): bool
    {
        return ($product->getStock()->getValue() + $adjustment) < 0;
    }
}