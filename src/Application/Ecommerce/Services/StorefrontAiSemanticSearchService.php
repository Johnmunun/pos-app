<?php

namespace Src\Application\Ecommerce\Services;

use Illuminate\Support\Facades\Http;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Recherche sémantique produits (catalogue vitrine).
 */
final class StorefrontAiSemanticSearchService
{
    /**
     * @return list<string> IDs produits pertinents (max 20)
     */
    public function searchProductIds(int $shopId, string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $products = ProductModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->limit(120)
            ->get(['id', 'name', 'description', 'sku', 'couleur', 'taille'])
            ->map(fn ($p) => [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'description' => (string) ($p->description ?? ''),
                'sku' => (string) ($p->sku ?? ''),
                'couleur' => (string) ($p->couleur ?? ''),
                'taille' => (string) ($p->taille ?? ''),
            ])->values()->all();

        return $this->rankWithOpenAiOrFallback($query, $products);
    }

    /**
     * @param list<array{id:string,name:string,description:string,sku:string,couleur:string,taille:string}> $products
     *
     * @return list<string>
     */
    private function rankWithOpenAiOrFallback(string $query, array $products): array
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey !== '') {
            try {
                $system = 'Tu classes des produits selon leur pertinence sémantique. Réponds uniquement en JSON.';
                $user = "Requête client: {$query}\nProduits JSON:\n".json_encode($products, JSON_UNESCAPED_UNICODE)
                    ."\nRéponds: {\"product_ids\":[...]} avec max 20 IDs, du plus pertinent au moins pertinent.";
                $res = Http::withToken($apiKey)->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 500,
                ]);
                if ($res->successful()) {
                    $content = trim((string) ($res->json('choices.0.message.content') ?? ''));
                    $json = $this->extractJson($content);
                    if (is_array($json) && is_array($json['product_ids'] ?? null)) {
                        $allowed = collect($products)->pluck('id')->all();

                        return array_values(array_slice(array_values(array_filter(array_map(
                            fn ($id) => in_array((string) $id, $allowed, true) ? (string) $id : null,
                            $json['product_ids']
                        ))), 0, 20));
                    }
                }
            } catch (\Throwable) {
                // lexical fallback
            }
        }

        $tokens = array_values(array_filter(preg_split('/\s+/', mb_strtolower($query)) ?: []));
        $scored = [];
        foreach ($products as $p) {
            $text = mb_strtolower(($p['name'] ?? '').' '.($p['description'] ?? '').' '.($p['couleur'] ?? '').' '.($p['taille'] ?? '').' '.($p['sku'] ?? ''));
            $score = 0;
            foreach ($tokens as $t) {
                if (mb_strlen($t) < 3) {
                    continue;
                }
                if (str_contains($text, $t)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = ['id' => (string) $p['id'], 'score' => $score];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice(array_map(fn ($x) => $x['id'], $scored), 0, 20));
    }

    /** @return array<string, mixed>|null */
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
}
