<?php

namespace Src\Application\Pharmacy\Services;

use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\Services\InventoryService;
use Src\Domain\Pharmacy\Services\ExpiryAlertService;

class DashboardService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private BatchRepositoryInterface $batchRepository,
        private InventoryService $inventoryService,
        private ExpiryAlertService $expiryService
    ) {}

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(string $shopId): array
    {
        return [
            'products' => $this->getProductStats($shopId),
            'inventory' => $this->getInventoryStats($shopId),
            'expiry' => $this->getExpiryStats($shopId),
            'alerts' => $this->getAlerts($shopId)
        ];
    }

    /**
     * Get product statistics
     */
    private function getProductStats(string $shopId): array
    {
        $allProducts = $this->productRepository->findByShop($shopId);
        $activeProducts = $this->productRepository->findByShop($shopId, ['active' => true]);
        
        return [
            'total' => count($allProducts),
            'active' => count($activeProducts),
            'inactive' => count($allProducts) - count($activeProducts),
            'by_type' => $this->getProductsByType($shopId)
        ];
    }

    /**
     * Get inventory statistics
     */
    private function getInventoryStats(string $shopId): array
    {
        $products = $this->productRepository->findByShop($shopId, ['active' => true]);
        
        $totalValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        
        foreach ($products as $product) {
            // Calculate inventory value
            $totalValue += $product->getPrice()->getAmount() * $product->getStock()->getValue();
            
            if ($this->inventoryService->isLowStock($product)) {
                $lowStockCount++;
            }
            
            if ($this->inventoryService->isOutOfStock($product)) {
                $outOfStockCount++;
            }
        }
        
        return [
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'stock_status' => $this->getStockStatusDistribution($products)
        ];
    }

    /**
     * Get expiry statistics
     */
    private function getExpiryStats(string $shopId): array
    {
        $expiringSoon = $this->batchRepository->getExpiringSoon($shopId, 30);
        $expired = $this->batchRepository->getExpired($shopId);
        $lowStock = $this->batchRepository->getLowStock($shopId, 10);
        
        return [
            'expiring_soon_count' => count($expiringSoon),
            'expired_count' => count($expired),
            'low_stock_batches' => count($lowStock),
            'critical_batches' => $this->countCriticalBatches($expiringSoon)
        ];
    }

    /**
     * Get system alerts
     */
    private function getAlerts(string $shopId): array
    {
        $alerts = [];
        
        // Low stock alerts
        $lowStockProducts = $this->productRepository->getLowStockProducts($shopId);
        if (!empty($lowStockProducts)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($lowStockProducts) . ' products are low in stock',
                'priority' => 'medium'
            ];
        }
        
        // Expired products alerts
        $expiredProducts = $this->productRepository->getExpiredProducts($shopId);
        if (!empty($expiredProducts)) {
            $alerts[] = [
                'type' => 'danger',
                'message' => count($expiredProducts) . ' products have expired',
                'priority' => 'high'
            ];
        }
        
        // Expiring soon alerts
        $expiringSoon = $this->batchRepository->getExpiringSoon($shopId, 7);
        if (!empty($expiringSoon)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($expiringSoon) . ' batches expire within 7 days',
                'priority' => 'high'
            ];
        }
        
        return $alerts;
    }

    /**
     * Get products grouped by medicine type
     */
    private function getProductsByType(string $shopId): array
    {
        $types = [];
        $medicineTypes = ['tablet', 'capsule', 'syrup', 'injection', 'cream', 'other'];
        
        foreach ($medicineTypes as $type) {
            $products = $this->productRepository->findByType($shopId, $type);
            $types[$type] = count($products);
        }
        
        return $types;
    }

    /**
     * Get stock status distribution
     */
    private function getStockStatusDistribution(array $products): array
    {
        $distribution = [
            'out_of_stock' => 0,
            'low_stock' => 0,
            'medium_stock' => 0,
            'good_stock' => 0
        ];
        
        foreach ($products as $product) {
            $status = $this->inventoryService->getStockStatus($product);
            $distribution[$status]++;
        }
        
        return $distribution;
    }

    /**
     * Count critical batches (expiring within 7 days)
     */
    private function countCriticalBatches(array $batches): int
    {
        $count = 0;
        foreach ($batches as $batch) {
            if ($this->expiryService->getExpiryStatus($batch) === 'critical') {
                $count++;
            }
        }
        return $count;
    }
}