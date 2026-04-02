<?php

namespace Src\Infrastructure\Common\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;

class ProductAiSeoController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if ($tenantId === null || $tenantId === '') {
            abort(403, 'Impossible de déterminer le tenant pour la facturation.');
        }

        $this->featureLimitService->assertFeatureEnabled($tenantId, 'ai.product.seo.generate');
        $this->featureLimitService->assertCanUseMonthlyFeature($tenantId, 'ai.product.seo.generate', 'la génération SEO IA');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'language' => 'nullable|string|in:fr,en',
        ]);

        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            return response()->json(['message' => 'Clé OpenAI non configurée sur le serveur (OPENAI_API_KEY).'], 503);
        }

        $language = (string) ($validated['language'] ?? 'fr');
        $name = trim((string) $validated['name']);
        $description = trim((string) ($validated['description'] ?? ''));

        $system = 'You are an ecommerce SEO assistant. Return JSON only.';
        $user = "Language: {$language}\nProduct name: {$name}\nProduct description: {$description}\n"
            ."Return strictly a JSON object with keys: meta_title, meta_description, slug. "
            ."meta_title max 60 chars, meta_description max 160 chars, slug lowercase with hyphens.";

        $response = Http::withToken($apiKey)
            ->timeout(45)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.3,
                'max_tokens' => 350,
            ]);

        if (!$response->successful()) {
            return response()->json(['message' => 'Le service SEO IA a refusé la requête.'], 502);
        }

        $content = trim((string) ($response->json('choices.0.message.content') ?? ''));
        $parsed = $this->extractJson($content);
        if ($parsed === null) {
            return response()->json(['message' => 'Réponse IA SEO illisible.'], 422);
        }

        $slug = $this->normalizeSlug((string) ($parsed['slug'] ?? $name));
        $payload = [
            'meta_title' => mb_substr(trim((string) ($parsed['meta_title'] ?? $name)), 0, 60),
            'meta_description' => mb_substr(trim((string) ($parsed['meta_description'] ?? $description)), 0, 160),
            'slug' => $slug !== '' ? $slug : $this->normalizeSlug($name),
        ];

        $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.product.seo.generate');

        return response()->json(['seo' => $payload]);
    }

    /** @return array<string,mixed>|null */
    private function extractJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    private function normalizeSlug(string $text): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($text))) ?? '';
        return trim($slug, '-');
    }

    private function resolveTenantId(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }
        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }
        $shopId = $user->shop_id ?? null;
        if (($shopId === null || $shopId === '') && method_exists($user, 'isRoot') && $user->isRoot()) {
            $shopId = $request->session()->get('current_storefront_shop_id') ?? $request->session()->get('current_shop_id');
        }
        if ($shopId === null || $shopId === '' || !Schema::hasTable('shops')) {
            return null;
        }
        $tenantId = DB::table('shops')->where('id', $shopId)->value('tenant_id');
        return $tenantId ? (string) $tenantId : null;
    }
}

