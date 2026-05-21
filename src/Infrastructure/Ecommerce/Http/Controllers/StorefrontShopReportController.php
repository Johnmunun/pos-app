<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Ecommerce\Services\EcommerceShopReportService;

final class StorefrontShopReportController
{
    public function __construct(
        private readonly EcommerceShopReportService $reportService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('storefront_shop');
        if (!$shop instanceof Shop) {
            abort(404, 'Boutique introuvable.');
        }

        if ($shop->ecommerce_report_suspended_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Cette boutique n\'est plus accessible.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:scam,counterfeit,inappropriate,spam,other',
            'details' => 'nullable|string|max:2000',
            'reporter_name' => 'nullable|string|max:120',
            'reporter_email' => 'nullable|email|max:190',
        ]);

        try {
            $this->reportService->submitReport(
                $shop,
                $validated,
                $request->ip(),
                $request->userAgent()
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 429);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Merci. Votre signalement a été transmis à notre équipe de modération.',
        ]);
    }
}
