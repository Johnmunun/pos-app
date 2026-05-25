<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Application\Common\Services\AssistantSalesProfitContextBuilder;
use Src\Application\Common\Services\AssistantUserFacingResponse;
use Src\Application\Pharmacy\Services\PharmacyAssistantContextService;

class PharmacyAssistantController
{
    public function __construct(
        private PharmacyAssistantContextService $contextService
    ) {}

    /**
     * POST /pharmacy/assistant/ask
     * Body: { "message": "Combien de ventes aujourd'hui ?" }
     * Réponse: { "answer": "...", "context_used": true }
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array|max:6',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        $message = trim($request->input('message'));
        if ($message === '') {
            return response()->json(['answer' => 'Veuillez poser une question.', 'context_used' => false]);
        }

        $enabled = config('pharmacy_assistant.enabled', true);
        if (! $enabled) {
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
            return $this->assistantJsonResponse([
                'answer' => $this->fallbackForError($context['error'], $context),
                'navigation' => null,
            ], false);
        }

        $systemPrompt = config('pharmacy_assistant.system_prompt', '');
        $driver = config('pharmacy_assistant.llm_driver', 'fallback');
        $apiKey = config('services.openai.api_key');

        $history = $this->normalizeHistory($request->input('history'));

        if ($driver === 'openai' && $apiKey !== null && $apiKey !== '') {
            try {
                $result = $this->callOpenAI($apiKey, $systemPrompt, $context, $message, $history);

                return $this->assistantJsonResponse($result, true);
            } catch (\Throwable $e) {
                Log::warning('Pharmacy assistant OpenAI error', ['message' => $e->getMessage()]);
            }
        }

        $result = $this->fallbackAnswer($context, $message);

        return $this->assistantJsonResponse($result, true);
    }

    /**
     * @param  array{answer?: string|null, navigation?: array|null}  $result
     */
    private function assistantJsonResponse(array $result, bool $contextUsed): JsonResponse
    {
        $result = AssistantUserFacingResponse::finalize($result);

        return response()->json([
            'answer' => $result['answer'] ?? null,
            'navigation' => $result['navigation'] ?? null,
            'context_used' => $contextUsed,
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
                if ($candidate !== '' && strlen($candidate) > 1 && ! in_array(mb_strtolower($candidate), $stopWords, true)) {
                    return $candidate;
                }
            }
        }
        $words = preg_split('/\s+/', trim($message), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            $wClean = preg_replace('/[^\p{L}\p{N}-]/u', '', $w);
            if (strlen($wClean) > 2 && ! in_array(mb_strtolower($wClean), $stopWords, true)) {
                return $wClean;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function normalizeHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }
        $out = [];
        foreach ($history as $turn) {
            if (! is_array($turn)) {
                continue;
            }
            $role = ($turn['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $out[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
            if (count($out) >= 6) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{answer: string|null, navigation: array|null}
     */
    private function callOpenAI(string $apiKey, string $systemPrompt, array $context, string $userMessage, array $history): array
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userContent = "Contexte boutique (JSON — source unique pour chiffres et listes ; les routes navigation sont internes, ne jamais les afficher à l'utilisateur):\n"
            .$contextJson
            ."\n\nQuestion actuelle: "
            .$userMessage;

        $chatMessages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $turn) {
            $chatMessages[] = [
                'role' => $turn['role'] === 'user' ? 'user' : 'assistant',
                'content' => $turn['content'],
            ];
        }
        $chatMessages[] = ['role' => 'user', 'content' => $userContent];

        /** @var Response $response */
        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $chatMessages,
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API error: '.$response->body());
        }

        $data = $response->json();
        $content = trim($data['choices'][0]['message']['content'] ?? '');
        $navigation = $this->parseNavigationResponse($content, $context);
        if ($navigation !== null) {
            return ['answer' => null, 'navigation' => $navigation];
        }

        return ['answer' => $content ?: 'Je n\'ai pas pu générer de réponse.', 'navigation' => null];
    }

    /**
     * Parse la réponse LLM : si c'est un JSON avec type "navigation" et route autorisée, retourne l'objet.
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
        if (! is_array($json) || ($json['type'] ?? '') !== 'navigation') {
            return null;
        }
        $route = $json['route'] ?? '';
        if ($route === '' || ! in_array($route, $allowedRoutes, true)) {
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

            return 'Aucune boutique associée. '.AssistantUserFacingResponse::formatAccessibleModulesList($nav, 5, 'disponibles');
        }

        return 'Données temporairement indisponibles.';
    }

    /**
     * @return array{answer: string|null, navigation: array|null}
     */
    private function fallbackAnswer(array $context, string $message): array
    {
        $lower = mb_strtolower($message);
        $userName = $context['user_name'] ?? 'Utilisateur';
        $sales = $context['sales_today'] ?? [];
        $totalAll = $context['sales_total_all_time'] ?? [];
        $salesByDay = $context['sales_last_30_days'] ?? [];
        $dashboard = $context['dashboard_summary'] ?? [];
        $customersCount = $context['customers_count'] ?? [];
        $nav = $context['navigation'] ?? [];
        $products = $context['products_matching'] ?? [];
        $alerts = $context['stock_alerts'] ?? [];
        $productsOutOfStock = $context['products_out_of_stock'] ?? [];
        $productsLowStock = $context['products_low_stock'] ?? [];
        $expiringSoonProducts = $context['expiring_soon_products'] ?? [];
        $currency = $context['currency'] ?? 'CDF';

        $greet = function (string $body) use ($userName): string {
            $hour = (int) now()->format('H');
            if ($hour < 12) {
                $hello = 'Bonjour';
            } elseif ($hour < 18) {
                $hello = 'Bon après-midi';
            } else {
                $hello = 'Bonsoir';
            }

            return sprintf("%s %s,\n\n%s", $hello, $userName, $body);
        };

        $profitAnswer = AssistantSalesProfitContextBuilder::tryAnswerProfitQuestion($message, $context, $currency);
        if ($profitAnswer !== null) {
            return ['answer' => $greet($profitAnswer), 'navigation' => null];
        }

        // Comparaison ventes aujourd'hui vs hier
        if ((str_contains($lower, 'plus') && str_contains($lower, 'vente')) || str_contains($lower, 'comparaison') && (str_contains($lower, 'aujourd') || str_contains($lower, 'hier'))) {
            $todaySales = $sales['total_sales'] ?? 0;
            $yesterday = now()->subDay()->format('Y-m-d');
            $yesterdaySales = 0;
            foreach ($salesByDay as $d) {
                if (isset($d['date']) && (str_starts_with((string) $d['date'], $yesterday) || $d['date'] === $yesterday)) {
                    $yesterdaySales = $d['total_sales'] ?? 0;
                    break;
                }
            }
            $diff = $todaySales - $yesterdaySales;
            $trend = $diff > 0 ? "📈 Une hausse de {$diff} vente(s) par rapport à hier." : ($diff < 0 ? '📉 Une baisse de '.abs($diff).' vente(s) par rapport à hier.' : '➖ Volume stable par rapport à hier.');
            $body = sprintf(
                "Voici la comparaison des ventes :\n\n🧾 Aujourd'hui : %d vente(s).\n📅 Hier : %d vente(s).\n\n%s\n\nSouhaitez-vous voir le détail par jour sur les 7 derniers jours ?",
                $todaySales,
                $yesterdaySales,
                $trend
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Quels produits expirent bientôt ?
        if ((str_contains($lower, 'expire') || str_contains($lower, 'expir')) && (str_contains($lower, 'bientôt') || str_contains($lower, 'produit') || str_contains($lower, 'quel'))) {
            if (empty($expiringSoonProducts)) {
                return ['answer' => "Aucun produit n'expire prochainement.", 'navigation' => null];
            }
            $lines = array_map(fn ($p) => $p['name'].' ('.($p['code'] ?: '—').') — expire dans '.($p['days_remaining'] ?? 0).' jour(s), '.($p['expiration_date'] ?? ''), $expiringSoonProducts);

            return ['answer' => 'Produits qui expirent bientôt : '.implode('. ', $lines), 'navigation' => null];
        }

        // Revenu total depuis le début / total boutique
        if ((str_contains($lower, 'total') || str_contains($lower, 'revenu') || str_contains($lower, 'chiffre')) && (str_contains($lower, 'début') || str_contains($lower, 'boutique') || str_contains($lower, 'depuis') || (str_contains($lower, 'tout') && str_contains($lower, 'temps')))) {
            $n = $totalAll['total_sales'] ?? 0;
            $rev = $totalAll['total_revenue'] ?? 0;
            $body = sprintf(
                "Voici le résumé global de la boutique :\n\n🧾 Nombre total de ventes : %d\n💰 Revenu cumulé : %s %s\n\nSouhaitez-vous que je vous affiche les ventes d'aujourd'hui ou d'une période précise ?",
                $n,
                number_format($rev, 0, ',', ' '),
                $currency
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Nombre de clients
        if (str_contains($lower, 'client') && (str_contains($lower, 'combien') || str_contains($lower, 'nombre') || str_contains($lower, 'total'))) {
            $totalCustomers = (int) ($customersCount['total_active'] ?? 0);
            $body = sprintf(
                "Voici l'information sur vos clients :\n\n👥 Nombre total de clients actifs : %d\n\nSouhaitez-vous que je vous affiche la liste des clients en retard de paiement ou ceux créés récemment ?",
                $totalCustomers
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Ventes aujourd'hui
        if (str_contains($lower, 'vente') && (str_contains($lower, 'aujourd') || (str_contains($lower, 'jour') && ! str_contains($lower, 'hier') && ! str_contains($lower, 'début') && ! str_contains($lower, '20') && ! str_contains($lower, 'février')))) {
            $n = $sales['total_sales'] ?? 0;
            $rev = $sales['total_revenue'] ?? 0;
            $body = sprintf(
                "Voici le résumé des ventes d'aujourd'hui :\n\n🧾 Nombre total de ventes : %d\n💰 Montant total : %s %s\n\nSouhaitez-vous le détail par transaction ou par produit ?",
                $n,
                number_format($rev, 0, ',', ' '),
                $currency
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Ventes hier (chercher la date d'hier dans sales_last_30_days)
        if (str_contains($lower, 'hier') && (str_contains($lower, 'vente') || str_contains($lower, 'revenu') || str_contains($lower, 'ventes'))) {
            $yesterday = now()->subDay()->format('Y-m-d');
            foreach ($salesByDay as $d) {
                if (isset($d['date']) && (str_starts_with((string) $d['date'], $yesterday) || $d['date'] === $yesterday)) {
                    $rev = $d['total_revenue'] ?? 0;
                    $cnt = $d['total_sales'] ?? 0;
                    $dateFr = Carbon::parse($yesterday)->locale('fr_FR')->isoFormat('D MMMM YYYY');
                    $body = sprintf(
                        "Voici les ventes d'hier (%s) :\n\n🧾 Nombre de ventes : %d\n💰 Montant total : %s %s\n\nSouhaitez-vous comparer hier avec aujourd'hui ou voir une autre date ?",
                        $dateFr,
                        $cnt,
                        number_format($rev, 0, ',', ' '),
                        $currency
                    );

                    return ['answer' => $greet($body), 'navigation' => null];
                }
            }
            $dateFr = Carbon::parse($yesterday)->locale('fr_FR')->isoFormat('D MMMM YYYY');
            $body = sprintf(
                "Pour hier (%s), aucune vente n'est enregistrée dans les données disponibles.\n\nSouhaitez-vous que je vous affiche les ventes d'un autre jour ou de la semaine en cours ?",
                $dateFr
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Ventes pour une date précise (ex. 20 février)
        $dateMatch = null;
        if (preg_match('/\b(\d{1,2})\s*(?:févr|fevr|février|fevrier)\b/ui', $message, $m) || preg_match('/\b(20)\s*(?:févr|fevr)\b/ui', $message, $m)) {
            $day = (int) $m[1];
            $year = (int) date('Y');
            $targetDate = sprintf('%04d-02-%02d', $year, $day);
            foreach ($salesByDay as $d) {
                if (isset($d['date']) && str_starts_with($d['date'], $targetDate)) {
                    $dateMatch = $d;
                    break;
                }
            }
            if ($dateMatch !== null) {
                $rev = $dateMatch['total_revenue'] ?? 0;
                $cnt = $dateMatch['total_sales'] ?? 0;
                $dateFr = Carbon::parse($dateMatch['date'])->locale('fr_FR')->isoFormat('D MMMM YYYY');
                $body = sprintf(
                    "Pour le %s :\n\n🧾 Nombre de ventes : %d\n💰 Montant total : %s %s\n\nSouhaitez-vous comparer cette date avec aujourd'hui ou voir une autre période ?",
                    $dateFr,
                    $cnt,
                    number_format($rev, 0, ',', ' '),
                    $currency
                );

                return ['answer' => $greet($body), 'navigation' => null];
            }
            $dateFr = $day.' février '.$year;
            $body = sprintf(
                "Les données pour le %s ne sont pas disponibles dans le contexte actuel (seuls les 30 derniers jours sont chargés côté assistant).\n\nVous pouvez consulter les rapports détaillés dans le module Rapports pour plus d'historique.",
                $dateFr
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Quels produits en rupture ? (liste)
        if ((str_contains($lower, 'rupture') || str_contains($lower, 'en rupture')) && (str_contains($lower, 'produit') || str_contains($lower, 'quel') || str_contains($lower, 'qui'))) {
            if (! empty($productsOutOfStock)) {
                $list = array_map(fn ($p) => $p['name'].' ('.($p['code'] ?: '—').')', $productsOutOfStock);
                $body = "Voici les produits actuellement en rupture de stock :\n\n";
                $body .= '📦 '.implode("\n📦 ", $list);
                $body .= "\n\nSouhaitez-vous voir les produits en stock bas ou les produits qui expirent bientôt ?";

                return ['answer' => $greet($body), 'navigation' => null];
            }
            $body = "Actuellement, aucun produit n'est en rupture de stock.\n\nVous pouvez me demander la liste des produits en stock bas ou des produits qui expirent bientôt.";

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Quels produits en stock bas ? (liste)
        if (str_contains($lower, 'stock bas') && (str_contains($lower, 'produit') || str_contains($lower, 'quel') || str_contains($lower, 'liste'))) {
            if (! empty($productsLowStock)) {
                $list = array_map(fn ($p) => $p['name'].' (stock '.$p['stock'].', min '.($p['minimum_stock'] ?? 0).')', $productsLowStock);
                $body = "Voici les produits en stock bas :\n\n";
                $body .= '⚠️ '.implode("\n⚠️ ", $list);
                $body .= "\n\nVous pouvez demander la liste des produits déjà en rupture ou la valeur totale du stock.";

                return ['answer' => $greet($body), 'navigation' => null];
            }
            $body = "Aucun produit n'est actuellement signalé en stock bas.\n\nSouhaitez-vous que je vous donne les produits en rupture ou un résumé global du stock ?";

            return ['answer' => $greet($body), 'navigation' => null];
        }

        if (str_contains($lower, 'stock') && (str_contains($lower, 'bas') || str_contains($lower, 'alerte'))) {
            $low = $alerts['low_stock_count'] ?? $dashboard['low_stock_count'] ?? 0;
            $out = $alerts['out_of_stock_count'] ?? 0;
            $body = sprintf(
                "Voici l'état global des alertes stock :\n\n⚠️ Produits en stock bas : %d\n⛔ Produits en rupture : %d\n\nVous pouvez me demander : \"quels produits en rupture ?\" ou \"quels produits en stock bas ?\" pour obtenir le détail.",
                $low,
                $out
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Proposition de bon d'achat / bon de commande
        if (str_contains($lower, 'bon d\'achat') || str_contains($lower, 'bon d achat') || str_contains($lower, 'bon de commande') || str_contains($lower, 'proposition d\'achat') || str_contains($lower, 'approvisionnement')) {
            $items = [];

            foreach ($productsOutOfStock as $p) {
                $min = (int) ($p['minimum_stock'] ?? 0);
                $suggested = $min > 0 ? max($min * 2, $min + 10) : 20;
                $items[] = [
                    'name' => $p['name'],
                    'code' => $p['code'] ?? '',
                    'qty' => $suggested,
                ];
            }
            foreach ($productsLowStock as $p) {
                $stockQty = (int) ($p['stock'] ?? 0);
                $min = (int) ($p['minimum_stock'] ?? 0);
                if ($min <= 0) {
                    $suggested = max(20 - $stockQty, 0);
                } else {
                    $target = max($min * 2, $min + 10);
                    $suggested = max($target - $stockQty, 0);
                }
                if ($suggested <= 0) {
                    continue;
                }
                $items[] = [
                    'name' => $p['name'],
                    'code' => $p['code'] ?? '',
                    'qty' => $suggested,
                ];
            }

            if (empty($items)) {
                $body = "D'après les informations disponibles, aucun produit n'est actuellement en rupture ou en stock bas. Il n'est donc pas nécessaire de générer un bon d'achat pour le moment.\n\nVous pouvez me demander les produits en rupture ou en stock bas pour vérifier l'état actuel du stock.";

                return ['answer' => $greet($body), 'navigation' => null];
            }

            // Limiter à 15 lignes pour garder la réponse lisible
            $items = array_slice($items, 0, 15);
            $lines = array_map(
                fn (array $i) => sprintf('💊 %s (code %s) — quantité à commander suggérée : %d', $i['name'], $i['code'] ?: '—', $i['qty']),
                $items
            );

            $body = "Voici une proposition de bon d'achat pour réapprovisionner la pharmacie, basée sur les produits en rupture et en stock bas :\n\n";
            $body .= implode("\n", $lines);
            $body .= "\n\nCette liste est une recommandation à partir des seuils de stock actuels. Le bon d'achat n'est pas encore créé dans le système : vous pouvez utiliser cette liste pour saisir un bon dans le module Achats.\n\nSouhaitez-vous que je vous affiche uniquement les produits en rupture, ou uniquement ceux en stock bas ?";

            return ['answer' => $greet($body), 'navigation' => null];
        }

        // Navigation : "où est la page / rapports / devises / paramètres / utilisateurs ?"
        if (str_contains($lower, 'où') || str_contains($lower, 'ou ') || str_contains($lower, 'trouver') || str_contains($lower, 'page') || str_contains($lower, 'navigation')) {
            $match = $this->matchNavigationIntent($lower, $nav);
            if ($match !== null) {
                return ['answer' => null, 'navigation' => $match];
            }

            return [
                'answer' => AssistantUserFacingResponse::formatAccessibleModulesList($nav, 10, 'de la pharmacie'),
                'navigation' => null,
            ];
        }

        if (! empty($products)) {
            $p = $products[0];
            $stockQty = $p['stock_quantity'] ?? $p['stock'] ?? 0;
            $sellingPrice = $p['selling_price'] ?? $p['sale_price'] ?? 0;
            $curr = $p['currency'] ?? $currency;
            if (count($products) === 1) {
                $exp = isset($p['expiration_date']) && $p['expiration_date'] ? "\nExpiration la plus proche : ".$p['expiration_date'].'.' : '';
                $cost = $p['cost_price'] ?? null;
                $marginBlock = '';
                if ($cost !== null) {
                    $unitMargin = (float) ($p['unit_margin'] ?? ($sellingPrice - $cost));
                    $marginBlock = sprintf(
                        "\nPrix d'achat : %s %s\nMarge unitaire : %s %s (%s %%)\nBénéfice potentiel sur stock : %s %s",
                        number_format((float) $cost, 0, ',', ' '),
                        $curr,
                        number_format($unitMargin, 0, ',', ' '),
                        $curr,
                        $p['margin_percent'] ?? '—',
                        number_format((float) ($p['profit_on_stock'] ?? 0), 0, ',', ' '),
                        $curr
                    );
                } else {
                    $marginBlock = "\nPrix d'achat : non renseigné — marge non calculable.";
                }
                $movements = $p['recent_stock_movements'] ?? [];
                $movBlock = '';
                if ($movements !== []) {
                    $movLines = array_map(
                        fn ($mv) => sprintf('- %s : %s %s (%s)', $mv['date'] ?? '', $mv['type'] ?? '', $mv['quantity'] ?? '', $mv['reference'] ?? '—'),
                        array_slice($movements, 0, 5)
                    );
                    $movBlock = "\n\nDerniers mouvements de stock :\n".implode("\n", $movLines);
                }
                $body = sprintf(
                    "Voici les informations sur le produit demandé :\n\nNom : %s (code %s)\nStock actuel : %d %s\nPrix de vente : %s %s\nStock minimum : %d%s%s%s",
                    $p['name'],
                    $p['code'] ?? '—',
                    $stockQty,
                    $p['unit'] ?? 'unité',
                    number_format((float) $sellingPrice, 0, ',', ' '),
                    $curr,
                    $p['minimum_stock'] ?? 0,
                    $exp,
                    $marginBlock,
                    $movBlock
                );

                return ['answer' => $greet($body), 'navigation' => null];
            }
            $names = array_map(fn ($x) => $x['name'].' ('.($x['code'] ?? '').')', array_slice($products, 0, 5));
            $body = "Plusieurs produits correspondent à votre demande. Lequel souhaitez-vous consulter plus en détail ?\n\n";
            $body .= '💊 '.implode("\n💊 ", $names);

            return ['answer' => $greet($body), 'navigation' => null];
        }

        if (str_contains($lower, 'résumé') || str_contains($lower, 'résume') || str_contains($lower, 'dashboard') || (str_contains($lower, 'valeur') && str_contains($lower, 'stock'))) {
            $pt = $dashboard['products_total'] ?? 0;
            $val = $dashboard['inventory_total_value'] ?? 0;
            $exp = $dashboard['expiring_soon_count'] ?? 0;
            $body = sprintf(
                "Voici un résumé rapide de la pharmacie :\n\n📦 Nombre total de produits : %d\n💰 Valeur estimée du stock : %s %s\n⏳ Produits expirant sous 30 jours : %d\n\nVous pouvez demander la liste des produits en rupture, en stock bas ou qui expirent bientôt pour plus de détail.",
                $pt,
                number_format($val, 0, ',', ' '),
                $currency,
                $exp
            );

            return ['answer' => $greet($body), 'navigation' => null];
        }

        $body = "Je ne trouve pas cette information dans les données actuellement chargées.\n\nVous pouvez par exemple me demander :\n- les ventes d'aujourd'hui ou d'hier,\n- les produits en rupture ou en stock bas,\n- les produits qui expirent bientôt,\n- la valeur totale du stock,\n- ou le détail d'un produit (nom ou code).";

        return ['answer' => $greet($body), 'navigation' => null];
    }

    /**
     * Pour une question de type "où est la page X", retourne un payload navigation si une entrée correspond.
     *
     * @param  array<int, array{name: string, route: string, label?: string, path?: string}>  $nav
     * @return array{type: string, label: string, route: string, method: string}|null
     */
    private function matchNavigationIntent(string $lowerMessage, array $nav): ?array
    {
        $keywords = [
            'devise' => ['devise', 'devises', 'monnaie', 'currencies'],
            'rapport' => ['rapport', 'rapports', 'report'],
            'paramètre' => ['paramètre', 'paramètres', 'parametre', 'parametres', 'settings', 'réglage', 'réglages'],
            'utilisateur' => ['utilisateur', 'utilisateurs', 'user', 'users', 'gestion des utilisateurs'],
        ];
        $routeByKey = [
            'devise' => '/settings/currencies',
            'rapport' => '/pharmacy/reports',
            'paramètre' => '/settings',
            'utilisateur' => '/admin/users',
        ];
        foreach ($keywords as $key => $terms) {
            foreach ($terms as $term) {
                if (str_contains($lowerMessage, $term)) {
                    $wantedRoute = $routeByKey[$key];
                    foreach ($nav as $n) {
                        $route = $n['route'] ?? '';
                        if ($route === $wantedRoute) {
                            return [
                                'type' => 'navigation',
                                'label' => $n['label'] ?? $n['name'] ?? 'Aller',
                                'route' => $route,
                                'method' => 'GET',
                            ];
                        }
                    }

                    return null;
                }
            }
        }

        return null;
    }
}
