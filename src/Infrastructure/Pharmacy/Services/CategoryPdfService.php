<?php

namespace Src\Infrastructure\Pharmacy\Services;

use Src\Infrastructure\Pharmacy\Models\CategoryModel;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Service: CategoryPdfService
 * 
 * Génère des PDFs propres pour les catégories
 */
class CategoryPdfService
{
    /**
     * Génère un PDF de la liste des catégories
     */
    public function generateCategoriesPdf(User $user, ?string $shopId = null, ?string $search = null): \Barryvdh\DomPDF\PDF
    {
        // Construire la query
        $query = CategoryModel::with(['parent', 'children', 'products']);
        
        if ($user->isRoot() && !$shopId) {
            // ROOT voit tout
        } else {
            $query->where('shop_id', $shopId);
        }
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $categories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        // Préparer les données pour le PDF
        $data = [
            'categories' => $categories->map(function ($category) {
                return [
                    'name' => $category->name,
                    'description' => $category->description ?? '-',
                    'parent' => $category->parent ? $category->parent->name : 'Catégorie principale',
                    'products_count' => $category->products()->count(),
                    'status' => $category->is_active ? 'Active' : 'Inactive',
                    'sort_order' => $category->sort_order ?? 0,
                ];
            })->toArray(),
            'total' => $categories->count(),
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'generated_by' => $user->name ?? $user->email,
            'shop_name' => $shopId ? ($user->shop->name ?? 'N/A') : 'Toutes les boutiques (ROOT)',
        ];
        
        // Générer le PDF avec une vue propre
        $pdf = Pdf::loadView('pharmacy.categories.pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf;
    }
}
