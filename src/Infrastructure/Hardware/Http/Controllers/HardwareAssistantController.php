<?php

namespace Src\Infrastructure\Hardware\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Application\Quincaillerie\Services\HardwareAssistantContextService;

/**
 * Assistant intelligent pour le module Hardware (Quincaillerie).
 * Utilise un contexte léger (agrégats, listes limitées) et, si configuré,
 * un LLM OpenAI avec un prompt dédié au domaine quincaillerie.
 */
class HardwareAssistantController
{
    public function __construct(
        private HardwareAssistantContextService $contextService
    ) {}

    /**
     * POST /hardware/assistant/ask
     * Body: { "message": "Quels produits en rupture ?" }
     * Réponse: { "answer": "...", "context_used": true } ou { "navigation": {...} }
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $message = trim($request->input('message'));
        if ($message === '') {
            return response()->json([
                'answer' => 'Veuillez poser une question.',
                'context_used' => false,
            ]);
        }

        $enabled = config('hardware_assistant.enabled', true);
        if (!$enabled) {
            return response()->json([
                'answer' => "L'assistant Quincaillerie est temporairement désactivé. Contactez l'administrateur.",
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

        $systemPrompt = (string) config('hardware_assistant.system_prompt', '');
        $driver = (string) config('hardware_assistant.llm_driver', 'fallback');
        $apiKey = (string) config('services.openai.api_key', '');

        if ($driver === 'openai' && $apiKey !== '') {
            try {
                $result = $this->callOpenAI($apiKey, $systemPrompt, $context, $message);
                return response()->json([
                    'answer' => $result['answer'],
                    'navigation' => $result['navigation'] ?? null,
                    'context_used' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Hardware assistant OpenAI error', ['message' => $e->getMessage()]);
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
        $prefixes = ['détail du ', 'détails du ', 'détail de ', 'détails de ', 'fiche ', 'info ', 'infos sur ', 'donne-moi ', 'donne moi ', 'stock ', 'prix ', 'produit ', 'article '];

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

    /**
     * @return array{answer: string|null, navigation: array|null}
     */
    private function callOpenAI(string $apiKey, string $systemPrompt, array $context, string $userMessage): array
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userContent = "Contexte (quincaillerie, données uniquement, ne pas inventer):\n" . $contextJson . "\n\nQuestion utilisateur: " . $userMessage;

        /** @var \Illuminate\Http\Client\Response $response */
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

        return [
            'answer' => $content !== '' ? $content : "Je n'ai pas pu générer de réponse.",
            'navigation' => null,
        ];
    }

    /**
     * Si la réponse du LLM est un JSON de type navigation valide, on le renvoie,
     * sinon null et on traite la réponse comme texte normal.
     *
     * @return array{type: string, label: string, route: string, method: string}|null
     */
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
            return "Vous devez être connecté pour utiliser l'assistant Quincaillerie.";
        }

        if ($error === 'no_shop') {
            $nav = $context['navigation'] ?? [];
            $lines = array_map(fn ($n) => $n['label'] . ' : ' . $n['path'], $nav);
            return 'Aucun magasin de quincaillerie associé. Modules accessibles : ' . implode(', ', array_slice($lines, 0, 5)) . '.';
        }

        return 'Données quincaillerie temporairement indisponibles.';
    }

    /**
     * Réponses fallback rapides basées uniquement sur le contexte JSON.
     *
     * @return array{answer: string|null, navigation: array|null}
     */
    private function fallbackAnswer(array $context, string $message): array
    {
        $lower = mb_strtolower($message);
        $userName = $context['user_name'] ?? 'Utilisateur';
        $productsSummary = $context['products_summary'] ?? [];
        $alerts = $context['stock_alerts'] ?? [];
        $outOfStock = $context['products_out_of_stock'] ?? [];
        $lowStock = $context['products_low_stock'] ?? [];
        $products = $context['products_matching'] ?? [];
        $currency = $context['currency'] ?? 'CDF';
        $nav = $context['navigation'] ?? [];
        $salesToday = $context['sales_today'] ?? null;

        $greet = function (string $body): string {
            // Pour éviter les répétitions de "Bonjour ..." à chaque message,
            // on retourne le corps tel quel côté fallback. Le LLM peut gérer
            // la salutation s'il est activé.
            return $body;
        };

        // Ventes du jour (combien de ventes aujourd'hui ?)
        if ($salesToday !== null
            && (str_contains($lower, 'aujourd') || str_contains($lower, "au jourd"))
            && (str_contains($lower, 'vente') || str_contains($lower, 'ventes'))
        ) {
            $count = (int) ($salesToday['total_sales'] ?? 0);
            $revenue = (float) ($salesToday['total_revenue'] ?? 0.0);
            $date = $salesToday['date'] ?? ($context['date'] ?? '');

            $body = sprintf(
                "Pour la quincaillerie, le %s, il y a eu %d vente(s) terminée(s) pour un total d'environ %s %s.",
                $date,
                $count,
                number_format($revenue, 0, ',', ' '),
                $currency
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Résumé / dashboard quincaillerie
        if (str_contains($lower, 'résumé') || str_contains($lower, 'dashboard') || (str_contains($lower, 'stock') && str_contains($lower, 'valeur'))) {
            $pt = (int) ($productsSummary['products_total'] ?? 0);
            $pa = (int) ($productsSummary['products_active'] ?? 0);
            $low = (int) ($alerts['low_stock_count'] ?? 0);
            $out = (int) ($alerts['out_of_stock_count'] ?? 0);

            $body = sprintf(
                "Voici un résumé rapide du module Quincaillerie :\n\n📦 Produits totaux : %d\n✅ Produits actifs : %d\n⚠️ Produits en stock bas : %d\n⛔ Produits en rupture : %d\n\nVous pouvez me demander la liste des produits en rupture, en stock bas ou le détail d'un article précis.",
                $pt,
                $pa,
                $low,
                $out
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Produits en rupture
        if ((str_contains($lower, 'rupture') || str_contains($lower, 'plus en stock')) && str_contains($lower, 'produit')) {
            if (!empty($outOfStock)) {
                $list = array_map(
                    fn ($p) => $p['name'] . ' (' . ($p['code'] ?: '—') . ')',
                    array_slice($outOfStock, 0, 15)
                );
                $body = "Produits actuellement en rupture de stock :\n\n";
                $body .= '🔧 ' . implode("\n🔧 ", $list);
                $body .= "\n\nVous pouvez créer un bon de commande dans le module Achats pour ces références.";
                return ['answer' => $greet($body), 'navigation' => null];
            }

            return ['answer' => $greet("Actuellement, aucun produit de quincaillerie n'est en rupture de stock."), 'navigation' => null];
        }

        // Produits en stock bas
        if (str_contains($lower, 'stock bas') || (str_contains($lower, 'alerte') && str_contains($lower, 'stock'))) {
            if (!empty($lowStock)) {
                $list = array_map(
                    fn ($p) => $p['name'] . ' (stock ' . ($p['stock'] ?? 0) . ', min ' . ($p['minimum_stock'] ?? 0) . ')',
                    array_slice($lowStock, 0, 15)
                );
                $body = "Produits en stock bas :\n\n";
                $body .= '⚠️ ' . implode("\n⚠️ ", $list);
                $body .= "\n\nPensez à réapprovisionner ces articles pour éviter la rupture.";
                return ['answer' => $greet($body), 'navigation' => null];
            }

            return ['answer' => $greet("Aucun produit n'est actuellement signalé en stock bas dans la quincaillerie."), 'navigation' => null];
        }

        // Détail d'un produit spécifique (via products_matching)
        if (!empty($products)) {
            if (count($products) === 1) {
                $p = $products[0];
                $stockQty = $p['stock_quantity'] ?? 0;
                $min = $p['minimum_stock'] ?? 0;
                $unit = $p['unit'] ?? 'UNITE';
                $price = $p['selling_price'] ?? 0;
                $curr = $p['currency'] ?? $currency;

                $body = sprintf(
                    "Voici les informations sur l'article demandé :\n\n🔧 Nom : %s (code %s)\n📦 Stock actuel : %s %s\n📉 Stock minimum : %s\n💰 Prix de vente : %s %s\n\nVous pouvez me demander de vérifier d'autres articles ou d'afficher la liste des produits en rupture.",
                    $p['name'],
                    $p['code'] ?: '—',
                    $stockQty,
                    $unit,
                    $min,
                    number_format((float) $price, 0, ',', ' '),
                    $curr
                );

                return ['answer' => $greet($body), 'navigation' => null];
            }

            $names = array_map(fn ($x) => $x['name'] . ' (' . ($x['code'] ?? '') . ')', $products);
            $body = "Plusieurs articles correspondent à votre recherche. Lequel souhaitez-vous détailler ?\n\n";
            $body .= '🔧 ' . implode("\n🔧 ", $names);
            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Intention explicite : ajouter des produits → aller vers création produit
        if ((str_contains($lower, 'ajouter') || str_contains($lower, 'ajout')) && str_contains($lower, 'produit')) {
            return [
                'answer' => null,
                'navigation' => [
                    'type' => 'navigation',
                    'label' => 'Créer un produit Quincaillerie',
                    'route' => '/hardware/products/create',
                    'method' => 'GET',
                ],
            ];
        }

        // Intention générale sur les produits sans mot-clé stock/rupture/prix → aller vers liste produits
        if (str_contains($lower, 'produit')) {
            $keywordsStock = ['rupture', 'stock bas', 'stock', 'prix', 'infos', 'information', 'détail', 'details', 'détails'];
            $isAnalytic = false;
            foreach ($keywordsStock as $kw) {
                if (str_contains($lower, $kw)) {
                    $isAnalytic = true;
                    break;
                }
            }
            if (!$isAnalytic) {
                $match = $this->matchNavigationIntent('produits', $nav);
                if ($match !== null) {
                    return ['answer' => null, 'navigation' => $match];
                }
            }
        }

        // Navigation simple : "où est la page X" pour la quincaillerie
        if (str_contains($lower, 'où') || str_contains($lower, 'ou ') || str_contains($lower, 'trouver') || str_contains($lower, 'page') || str_contains($lower, 'navigation')) {
            $match = $this->matchNavigationIntent($lower, $nav);
            if ($match !== null) {
                return ['answer' => null, 'navigation' => $match];
            }
            $list = array_slice(array_map(fn ($n) => $n['label'] . ' (' . $n['path'] . ')', $nav), 0, 10);
            return ['answer' => $greet('Modules Quincaillerie accessibles : ' . implode('. ', $list)), 'navigation' => null];
        }

        // Réponse générique si aucune règle spécifique ne s'applique
        $body = "Je peux vous aider sur :\n\n"
            . "• l'état du stock (produits en rupture, en stock bas),\n"
            . "• le détail d'un article (nom, code ou code-barres),\n"
            . "• la navigation vers les pages du module Quincaillerie (produits, catégories, stock, ventes, achats).\n\n"
            . "Par exemple :\n"
            . "- \"Quels produits de quincaillerie sont en rupture ?\"\n"
            . "- \"Produits en stock bas ?\"\n"
            . "- \"Infos sur la vis 8x40 ?\"\n"
            . "- \"Où est la page des ventes quincaillerie ?\"";

        return ['answer' => $greet($body), 'navigation' => null];
    }

    /**
     * Retourne un payload de navigation si la question fait clairement référence
     * à une page connue (produits, stock, ventes, achats, rapports).
     *
     * @param array<int, array{name: string, route: string, label?: string, path?: string}> $nav
     * @return array{type: string, label: string, route: string, method: string}|null
     */
    private function matchNavigationIntent(string $lowerMessage, array $nav): ?array
    {
        $keywords = [
            'produits' => ['produit', 'produits', 'articles', 'catalogue'],
            'stock' => ['stock', 'magasin', 'inventaire'],
            'ventes' => ['vente', 'ventes', 'caisse'],
            'achats' => ['achat', 'achats', 'bon de commande'],
            'rapports' => ['rapport', 'rapports', 'analyse'],
            'categories' => ['catégorie', 'catégories'],
        ];

        $routeByKey = [
            'produits' => '/hardware/products',
            'stock' => '/hardware/stock',
            'ventes' => '/hardware/sales',
            'achats' => '/hardware/purchases',
            'rapports' => '/hardware/reports',
            'categories' => '/hardware/categories',
        ];

        foreach ($keywords as $key => $terms) {
            foreach ($terms as $term) {
                if (str_contains($lowerMessage, $term)) {
                    $wantedRoute = $routeByKey[$key] ?? null;
                    if ($wantedRoute === null) {
                        continue;
                    }
                    foreach ($nav as $n) {
                        $route = $n['route'] ?? $n['path'] ?? '';
                        if ($route === $wantedRoute) {
                            return [
                                'type' => 'navigation',
                                'label' => $n['label'] ?? $n['name'] ?? 'Aller',
                                'route' => $route,
                                'method' => 'GET',
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }
}

