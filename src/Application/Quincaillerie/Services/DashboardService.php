<?php

namespace Src\Application\Quincaillerie\Services;

use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\SupplierRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CustomerRepositoryInterface;

class DashboardService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private SupplierRepositoryInterface $supplierRepository,
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(string $shopId): array
    {
        return [
            'products' => $this->getProductStats($shopId),
            'inventory' => $this->getInventoryStats($shopId),
            'categories' => $this->getCategoryStats($shopId),
            'suppliers' => $this->getSupplierStats($shopId),
            'customers' => $this->getCustomerStats($shopId),
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
        ];
    }

    /**
     * Get inventory statistics
     */
    private function getInventoryStats(string $shopId): array
    {
        $products = $this->productRepository->findByShop($shopId, ['active' => true]);
        
        $totalValue = 0.0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        
        $stockStatus = [
            'out_of_stock' => 0,
            'low_stock' => 0,
            'medium_stock' => 0,
            'good_stock' => 0
        ];
        
        foreach ($products as $product) {
            // Calculate inventory value
            $totalValue += $product->getPrice()->getAmount() * $product->getStock()->getValue();
            
            $stock = $product->getStock()->getValue();
            $minimumStock = $product->getMinimumStock()->getValue();
            
            if ($stock <= 0) {
                $outOfStockCount++;
                $stockStatus['out_of_stock']++;
            } elseif ($stock <= $minimumStock) {
                $lowStockCount++;
                $stockStatus['low_stock']++;
            } elseif ($stock <= $minimumStock * 2) {
                $stockStatus['medium_stock']++;
            } else {
                $stockStatus['good_stock']++;
            }
        }
        
        return [
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'stock_status' => $stockStatus
        ];
    }

    /**
     * Get category statistics
     */
    private function getCategoryStats(string $shopId): array
    {
        $categories = $this->categoryRepository->findByShop($shopId, false);
        $activeCategories = array_filter($categories, fn($cat) => $cat->isActive());
        
        return [
            'total' => count($categories),
            'active' => count($activeCategories),
        ];
    }

    /**
     * Get supplier statistics
     */
    private function getSupplierStats(string $shopId): array
    {
        $shopIdInt = (int) $shopId;
        $suppliers = $this->supplierRepository->findByShop($shopIdInt);
        $activeSuppliers = array_filter($suppliers, fn($sup) => $sup->isActive());
        
        return [
            'total' => count($suppliers),
            'active' => count($activeSuppliers),
        ];
    }

    /**
     * Get customer statistics
     */
    private function getCustomerStats(string $shopId): array
    {
        $shopIdInt = (int) $shopId;
        $customers = $this->customerRepository->findByShop($shopIdInt);
        $activeCustomers = array_filter($customers, fn($cust) => $cust->isActive());
        
        return [
            'total' => count($customers),
            'active' => count($activeCustomers),
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
                'message' => count($lowStockProducts) . ' produits ont un stock bas',
                'priority' => 'medium'
            ];
        }
        
        // Out of stock alerts
        $products = $this->productRepository->findByShop($shopId, ['active' => true]);
        $outOfStock = array_filter($products, fn($p) => $p->getStock()->getValue() <= 0);
        if (!empty($outOfStock)) {
            $alerts[] = [
                'type' => 'danger',
                'message' => count($outOfStock) . ' produits sont en rupture de stock',
                'priority' => 'high'
            ];
        }
        
        return $alerts;
    }
}
