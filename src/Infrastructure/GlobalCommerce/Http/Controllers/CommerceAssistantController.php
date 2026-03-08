<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Application\GlobalCommerce\Services\CommerceAssistantContextService;

class CommerceAssistantController
{
    public function __construct(
        private CommerceAssistantContextService $contextService
    ) {}

    /**
     * POST /commerce/assistant/ask
     * Body: { "message": "Combien de ventes aujourd'hui ?" }
     * Réponse: { "answer": "...", "context_used": true }
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $message = trim($request->input('message'));
        if ($message === '') {
            return response()->json(['answer' => 'Veuillez poser une question.', 'context_used' => false]);
        }

        $enabled = config('commerce_assistant.enabled', true);
        if (!$enabled) {
            return response()->json([
                'answer' => "L'assistant est temporairement désactivé. Contactez l'administrateur.",
                'context_used' => false,
            ]);
        }

        $productSearch = $this->extractProductSearch($message);
        $permissions = $request->user() && method_exists($request->user(), 'permissionCodes')
            ? $request->user()->permissionCodes()
            : [];
        $context = $this->contextService->getContext($request, $productSearch, $permissions);

        if (isset($context['error'])) {
            return response()->json([
                'answer' => $this->fallbackForError($context['error'], $context),
                'navigation' => null,
                'context_used' => false,
            ]);
        }

        $systemPrompt = config('commerce_assistant.system_prompt', '');
        $driver = config('commerce_assistant.llm_driver', 'fallback');
        $apiKey = config('services.openai.api_key');

        if ($driver === 'openai' && $apiKey !== null && $apiKey !== '') {
            try {
                $result = $this->callOpenAI($apiKey, $systemPrompt, $context, $message);
                return response()->json([
                    'answer' => $result['answer'],
                    'navigation' => $result['navigation'] ?? null,
                    'context_used' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Commerce assistant OpenAI error', ['message' => $e->getMessage()]);
            }
        }

        $result = $this->fallbackAnswer($context, $message);
        return response()->json([
            'answer' => $result['answer'],
            'navigation' => $result['navigation'] ?? null,
            'context_used' => true,
        ]);
    }

    private function extractProductSearch(string $message): ?string
    {
        $lower = mb_strtolower(trim($message));
        $stopWords = ['les', 'des', 'du', 'de', 'la', 'le', 'un', 'une', 'donne', 'moi', 'infos', 'sur', 'fiche', 'détail', 'détails', 'produit', 'stock', 'prix', 'quel', 'quelle', 'info', 'stocks', 'prix'];
        $prefixes = ['détail du ', 'détails du ', 'détail de ', 'détails de ', 'fiche ', 'info ', 'infos sur ', 'donne-moi ', 'donne moi ', 'stock ', 'prix ', 'produit '];
        foreach ($prefixes as $p) {
            $pos = mb_strpos($lower, $p);
            if ($pos !== false) {
                $rest = trim(mb_substr($message, $pos + mb_strlen($p)));
                $candidate = preg_split('/\s+/', $rest, 2, PREG_SPLIT_NO_EMPTY)[0] ?? '';
                if ($candidate !== '' && strlen($candidate) > 1 && !in_array(mb_strtolower($candidate), $stopWords, true)) {
                    return $candidate;
                }
            }
        }
        $words = preg_split('/\s+/', trim($message), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            $wClean = preg_replace('/[^\p{L}\p{N}-]/u', '', $w);
            if (strlen($wClean) > 2 && !in_array(mb_strtolower($wClean), $stopWords, true)) {
                return $wClean;
            }
        }
        return null;
    }

    private function callOpenAI(string $apiKey, string $systemPrompt, array $context, string $userMessage): array
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userContent = "Contexte (données uniquement, ne pas inventer):\n" . $contextJson . "\n\nQuestion utilisateur: " . $userMessage;

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        $content = trim($data['choices'][0]['message']['content'] ?? '');
        $navigation = $this->parseNavigationResponse($content, $context);
        if ($navigation !== null) {
            return ['answer' => null, 'navigation' => $navigation];
        }
        return ['answer' => $content ?: 'Je n\'ai pas pu générer de réponse.', 'navigation' => null];
    }

    private function parseNavigationResponse(string $content, array $context): ?array
    {
        $allowedRoutes = array_column($context['navigation'] ?? [], 'route');
        $content = trim($content);
        if ($content === '') {
            return null;
        }
        if (preg_match('/```(?:json)?\s*(\{[^`]+\})\s*```/s', $content, $m)) {
            $content = trim($m[1]);
        }
        $json = json_decode($content, true);
        if (!is_array($json) || ($json['type'] ?? '') !== 'navigation') {
            return null;
        }
        $route = $json['route'] ?? '';
        if ($route === '' || !in_array($route, $allowedRoutes, true)) {
            return null;
        }
        return [
            'type' => 'navigation',
            'label' => $json['label'] ?? 'Aller',
            'route' => $route,
            'method' => $json['method'] ?? 'GET',
        ];
    }

    private function fallbackForError(string $error, array $context): string
    {
        if ($error === 'non_authenticated') {
            return 'Vous devez être connecté pour utiliser l\'assistant.';
        }
        if ($error === 'no_shop') {
            $nav = $context['navigation'] ?? [];
            $lines = array_map(fn ($n) => $n['label'] . ' : ' . $n['path'], $nav);
            return 'Aucune boutique associée. Vous pouvez accéder aux modules : ' . implode(', ', array_slice($lines, 0, 5)) . '.';
        }
        return 'Données temporairement indisponibles.';
    }

    private function fallbackAnswer(array $context, string $message): array
    {
        $lower = mb_strtolower($message);
        $userName = $context['user_name'] ?? 'Utilisateur';
        $sales = $context['sales_today'] ?? [];
        $totalAll = $context['sales_total_all_time'] ?? [];
        $currency = $context['currency'] ?? 'CDF';
        $products = $context['products_matching'] ?? [];
        $alerts = $context['stock_alerts'] ?? [];
        $productsOutOfStock = $context['products_out_of_stock'] ?? [];
        $productsLowStock = $context['products_low_stock'] ?? [];
        $nav = $context['navigation'] ?? [];

        $greet = function (string $body) use ($userName): string {
            $hour = (int) now()->format('H');
            $hello = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
            return sprintf("%s %s,\n\n%s", $hello, $userName, $body);
        };

        // Ventes aujourd'hui
        if (str_contains($lower, 'vente') && str_contains($lower, 'aujourd')) {
            $n = $sales['total_sales'] ?? 0;
            $rev = $sales['total_revenue'] ?? 0;
            $body = sprintf(
                "Voici le résumé des ventes d'aujourd'hui :\n\n🧾 Nombre total de ventes : %d\n💰 Montant total : %s %s",
                $n,
                number_format($rev, 0, ',', ' '),
                $currency
            );
            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Produits en rupture
        if (str_contains($lower, 'rupture') || (str_contains($lower, 'stock') && str_contains($lower, 'alerte'))) {
            $out = $alerts['out_of_stock_count'] ?? 0;
            $low = $alerts['low_stock_count'] ?? 0;
            if (!empty($productsOutOfStock)) {
                $list = array_map(fn ($p) => $p['name'] . ' (' . ($p['code'] ?: '—') . ')', array_slice($productsOutOfStock, 0, 10));
                $body = "Produits en rupture :\n\n📦 " . implode("\n📦 ", $list);
                return ['answer' => $greet($body), 'navigation' => null];
            }
            $body = sprintf("⚠️ Produits en stock bas : %d\n⛔ Produits en rupture : %d", $low, $out);
            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Navigation
        if (str_contains($lower, 'où') || str_contains($lower, 'trouver')) {
            $list = array_slice(array_map(fn ($n) => $n['label'] . ' (' . $n['path'] . ')', $nav), 0, 10);
            return ['answer' => 'Modules accessibles : ' . implode('. ', $list), 'navigation' => null];
        }

        // Produit spécifique
        if (!empty($products)) {
            $p = $products[0];
            $body = sprintf(
                "💊 %s (code %s)\n📦 Stock : %s\n💰 Prix : %s %s",
                $p['name'],
                $p['code'] ?? '—',
                $p['stock_quantity'] ?? 0,
                number_format($p['selling_price'] ?? 0, 0, ',', ' '),
                $p['currency'] ?? $currency
            );
            return ['answer' => $greet($body), 'navigation' => null];
        }

        $body = "Je peux vous aider avec :\n- les ventes d'aujourd'hui\n- les produits en rupture ou en stock bas\n- le détail d'un produit\n- la navigation dans le système";
        return ['answer' => $greet($body), 'navigation' => null];
    }
}
