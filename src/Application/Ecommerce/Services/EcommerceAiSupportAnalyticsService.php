<?php

namespace Src\Application\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Infrastructure\Ecommerce\Models\EcommerceAiSupportInteractionModel;

final class EcommerceAiSupportAnalyticsService
{
    public function logAsk(
        int $shopId,
        ?int $tenantId,
        ?string $topic,
        string $userMessage,
        string $assistantAnswer,
        int $productsShown,
        ?string $ipAddress
    ): string {
        $id = (string) Str::uuid();

        EcommerceAiSupportInteractionModel::query()->create([
            'id' => $id,
            'shop_id' => $shopId,
            'tenant_id' => $tenantId,
            'topic' => $topic,
            'user_message' => mb_substr($userMessage, 0, 500),
            'assistant_excerpt' => mb_substr($assistantAnswer, 0, 500),
            'products_shown' => $productsShown,
            'ip_address' => $ipAddress,
        ]);

        return $id;
    }

    public function recordFeedback(string $interactionId, int $shopId, string $feedback): bool
    {
        if (!in_array($feedback, [
            EcommerceAiSupportInteractionModel::FEEDBACK_HELPFUL,
            EcommerceAiSupportInteractionModel::FEEDBACK_NOT_HELPFUL,
        ], true)) {
            return false;
        }

        $updated = EcommerceAiSupportInteractionModel::query()
            ->where('id', $interactionId)
            ->where('shop_id', $shopId)
            ->whereNull('feedback')
            ->update([
                'feedback' => $feedback,
                'feedback_at' => now(),
            ]);

        return $updated > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function statsForShop(int $shopId, int $days = 30): array
    {
        $since = now()->subDays($days);

        $base = EcommerceAiSupportInteractionModel::query()
            ->where('shop_id', $shopId)
            ->where('created_at', '>=', $since);

        $totalAsks = (int) (clone $base)->count();
        $withFeedback = (clone $base)->whereNotNull('feedback');
        $helpful = (int) (clone $withFeedback)->where('feedback', EcommerceAiSupportInteractionModel::FEEDBACK_HELPFUL)->count();
        $notHelpful = (int) (clone $withFeedback)->where('feedback', EcommerceAiSupportInteractionModel::FEEDBACK_NOT_HELPFUL)->count();
        $feedbackTotal = $helpful + $notHelpful;

        $topTopics = (clone $base)
            ->select('topic', DB::raw('COUNT(*) as total'))
            ->whereNotNull('topic')
            ->groupBy('topic')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'topic' => (string) $row->topic,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $productSuggestions = (int) (clone $base)->where('products_shown', '>', 0)->count();

        return [
            'days' => $days,
            'total_asks' => $totalAsks,
            'product_suggestions' => $productSuggestions,
            'feedback_helpful' => $helpful,
            'feedback_not_helpful' => $notHelpful,
            'feedback_rate_percent' => $feedbackTotal > 0 ? (int) round(($helpful / $feedbackTotal) * 100) : null,
            'top_topics' => $topTopics,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, EcommerceAiSupportInteractionModel>
     */
    public function interactionsForExport(int $shopId, int $days = 30)
    {
        return EcommerceAiSupportInteractionModel::query()
            ->where('shop_id', $shopId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get();
    }

    public static function topicLabel(?string $topic): string
    {
        return match ($topic) {
            'shipping' => 'Livraison',
            'returns' => 'Retours',
            'order_status' => 'Commande',
            'availability' => 'Disponibilité',
            'product_search' => 'Recherche produits',
            'general' => 'Général',
            default => $topic ?? '—',
        };
    }
}
