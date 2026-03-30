<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Studio IA marketing (plans Pro / Enterprise + feature ecommerce.marketing.pro).
 */
class EcommerceMarketingAiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $shopId = $this->resolveShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $validated = $request->validate([
            'brief' => 'required|string|max:1200',
            'channel' => 'nullable|string|in:facebook,instagram,tiktok,google,newsletter,generic',
            'tone' => 'nullable|string|in:pro,direct,luxe,amicable',
            'offer' => 'nullable|string|max:400',
        ]);

        $brief = trim($validated['brief']);
        $channel = $validated['channel'] ?? 'generic';
        $tone = $validated['tone'] ?? 'pro';
        $offer = isset($validated['offer']) ? trim((string) $validated['offer']) : '';

        $system = <<<'PROMPT'
Tu es un stratège marketing senior (performance + branding) pour une boutique e-commerce.
Tu réponds en français, de façon actionnable et professionnelle.
Structure ta réponse avec des titres courts en ## (markdown), listes à puces, et des exemples de textes prêts à copier-coller.
Ne invente pas de chiffres de CA ni de garanties légales. Si une info manque, pose une question en fin de réponse (max 2 questions).
Inclus quand c’est pertinent : accroches, corps de message, CTA, idées d’UTM (utm_source, utm_medium, utm_campaign), et 2 variantes A/B pour les publicités.
PROMPT;

        $payload = [
            'boutique' => $shop->name,
            'canal' => $channel,
            'ton' => $tone,
            'brief' => $brief,
            'offre' => $offer,
        ];

        $userContent = "Contexte JSON:\n"
            .json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nProduis une recommandation marketing complète pour ce brief.";

        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey !== '') {
            try {
                $answer = $this->callOpenAI($apiKey, $system, $userContent);

                return response()->json(['answer' => $answer]);
            } catch (\Throwable $e) {
                Log::warning('Ecommerce marketing AI error', ['message' => $e->getMessage()]);
            }
        }

        return response()->json([
            'answer' => $this->fallbackAnswer($shop->name, $channel, $tone, $brief, $offer),
        ]);
    }

    private function resolveShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }

        return (string) $shopId;
    }

    private function callOpenAI(string $apiKey, string $systemPrompt, string $userContent): string
    {
        $response = Http::withToken($apiKey)
            ->timeout(45)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'max_tokens' => 1400,
                'temperature' => 0.45,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: '.$response->body());
        }

        $data = $response->json();
        $content = trim($data['choices'][0]['message']['content'] ?? '');

        return $content !== '' ? $content : $this->fallbackAnswer('', 'generic', 'pro', $userContent, '');
    }

    private function fallbackAnswer(string $shopName, string $channel, string $tone, string $brief, string $offer): string
    {
        $name = $shopName !== '' ? $shopName : 'votre boutique';

        return "## Plan d’action rapide\n\n"
            ."- **Cible** : reformulez en une phrase le problème résolu par {$name} (à partir de votre brief).\n"
            ."- **Canal prioritaire** : {$channel} — une campagne test sur 7 jours avec un budget modéré et une audience lookalike ou intérêts proches.\n"
            ."- **Message** : accroche + preuve sociale (avis, chiffre réel si vous en avez) + CTA unique.\n\n"
            ."## Exemple d’accroches ({$tone})\n\n"
            ."1. « Ce que nos clients reprennent en priorité chez {$name} »\n"
            ."2. « Livraison claire, retours simples : commandez l’esprit tranquille »\n\n"
            .($offer !== '' ? "## Votre offre\n\n{$offer}\n\n" : '')
            ."## UTM suggérées\n\n"
            ."- `utm_source={$channel}`\n"
            ."- `utm_medium=paid_social` ou `email` selon le canal\n"
            .'- `utm_campaign=lancement_'.date('Ymd')."`\n\n"
            ."## Assistant IA\n\n"
            ."Pour des propositions plus riches, configurez `OPENAI_API_KEY` sur le serveur. "
            ."En attendant, détaillez votre brief (produit vedette, prix, délai, promotion) pour affiner ce canevas.\n\n"
            ."## Rappel du brief\n\n".$brief;
    }
}
