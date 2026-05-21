<?php

namespace Src\Application\Ecommerce\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Http;

/**
 * Génère des paires question/réponse FAQ pour l’assistant vitrine.
 */
final class EcommerceAiSupportFaqSuggestionService
{
    /**
     * @param list<array{question: string, answer: string}> $existingFaq
     *
     * @return list<array{question: string, answer: string}>
     */
    public function suggest(Shop $shop, array $cfg, array $existingFaq, int $count = 5): array
    {
        $count = max(1, min(8, $count));
        $existingFaq = EcommerceAiSupportFaqMatcher::normalizeList($existingFaq);
        $slotsLeft = max(0, EcommerceAiSupportFaqMatcher::MAX_ITEMS - count($existingFaq));
        if ($slotsLeft === 0) {
            return [];
        }
        $count = min($count, $slotsLeft);

        $context = $this->buildShopContext($shop, $cfg, $existingFaq);
        $generated = $this->callOpenAi($context, $count);
        if ($generated !== []) {
            return $this->dedupeAgainstExisting($generated, $existingFaq);
        }

        return $this->fallbackSuggestions($shop, $cfg, $count);
    }

    /**
     * @param list<array{question: string, answer: string}> $existingFaq
     *
     * @return array<string, mixed>
     */
    private function buildShopContext(Shop $shop, array $cfg, array $existingFaq): array
    {
        return [
            'shop_name' => (string) $shop->name,
            'city' => (string) ($shop->city ?? ''),
            'country' => (string) ($shop->country ?? ''),
            'phone' => (string) ($shop->phone ?? ''),
            'email' => (string) ($shop->email ?? ''),
            'currency' => (string) ($shop->currency ?? ''),
            'shipping_policy' => (string) ($cfg['ai_support_shipping_policy'] ?? ''),
            'returns_policy' => (string) ($cfg['ai_support_returns_policy'] ?? ''),
            'flat_shipping_enabled' => (bool) ($cfg['storefront_use_flat_shipping'] ?? false),
            'flat_shipping_amount' => (float) ($cfg['storefront_flat_shipping_amount'] ?? 0),
            'tone' => (string) ($cfg['ai_support_tone'] ?? 'friendly'),
            'existing_faq_questions' => array_map(
                fn ($f) => (string) ($f['question'] ?? ''),
                $existingFaq
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<array{question: string, answer: string}>
     */
    private function callOpenAi(array $context, int $count): array
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            return [];
        }

        $system = 'Tu rédiges des FAQ pour l’assistant client d’une boutique e-commerce en français. '
            .'Réponds UNIQUEMENT avec un JSON valide: {"faqs":[{"question":"...","answer":"..."}]}. '
            ."Génère exactement {$count} entrées distinctes, questions naturelles (comme un client), réponses courtes (2-4 phrases), "
            .'factuelles, basées sur le contexte fourni. N’invente pas d’horaires, délais chiffrés ni de garanties non mentionnées. '
            .'Ne duplique pas les questions déjà listées dans existing_faq_questions. '
            .'Couvre des thèmes variés: livraison, retours, paiement, contact, délais, zone géographique si pertinent.';

        try {
            $res = Http::withToken($apiKey)->timeout(35)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => 'Contexte boutique JSON:\n'.json_encode($context, JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.35,
                'max_tokens' => 1200,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$res->successful()) {
                return [];
            }

            $content = trim((string) ($res->json('choices.0.message.content') ?? ''));
            $decoded = $this->extractFaqsJson($content);

            return EcommerceAiSupportFaqMatcher::normalizeList($decoded);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function extractFaqsJson(string $content): array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['faqs']) && is_array($decoded['faqs'])) {
            return $decoded['faqs'];
        }
        if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded) && isset($decoded['faqs']) && is_array($decoded['faqs'])) {
                return $decoded['faqs'];
            }
        }

        return [];
    }

    /**
     * @param list<array{question: string, answer: string}> $generated
     * @param list<array{question: string, answer: string}> $existingFaq
     *
     * @return list<array{question: string, answer: string}>
     */
    private function dedupeAgainstExisting(array $generated, array $existingFaq): array
    {
        $existingLower = array_map(
            fn ($f) => mb_strtolower(trim((string) ($f['question'] ?? ''))),
            $existingFaq
        );
        $out = [];
        foreach ($generated as $item) {
            $q = mb_strtolower(trim((string) ($item['question'] ?? '')));
            if ($q === '' || in_array($q, $existingLower, true)) {
                continue;
            }
            $duplicate = false;
            foreach ($out as $added) {
                similar_text($q, mb_strtolower($added['question']), $pct);
                if ($pct > 75) {
                    $duplicate = true;
                    break;
                }
            }
            if (!$duplicate) {
                $out[] = $item;
                $existingLower[] = $q;
            }
        }

        return $out;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function fallbackSuggestions(Shop $shop, array $cfg, int $count): array
    {
        $name = (string) $shop->name;
        $pool = [];

        $shipping = trim((string) ($cfg['ai_support_shipping_policy'] ?? ''));
        if ($shipping !== '') {
            $pool[] = [
                'question' => 'Quels sont vos délais et frais de livraison ?',
                'answer' => $shipping,
            ];
        } else {
            $pool[] = [
                'question' => 'Livrez-vous dans ma ville ?',
                'answer' => "La boutique {$name} livre selon votre zone. Indiquez votre ville lors de la commande ou contactez-nous pour une estimation précise.",
            ];
        }

        $returns = trim((string) ($cfg['ai_support_returns_policy'] ?? ''));
        if ($returns !== '') {
            $pool[] = [
                'question' => 'Quelle est votre politique de retour ?',
                'answer' => $returns,
            ];
        } else {
            $pool[] = [
                'question' => 'Puis-je retourner un article ?',
                'answer' => "Les retours sont traités au cas par cas par {$name}. Contactez-nous avec votre numéro de commande et l’état du produit.",
            ];
        }

        if ((bool) ($cfg['storefront_use_flat_shipping'] ?? false)) {
            $amount = number_format((float) ($cfg['storefront_flat_shipping_amount'] ?? 0), 2, ',', ' ');
            $pool[] = [
                'question' => 'Quels sont les frais de port ?',
                'answer' => "Des frais de livraison fixes de {$amount} s’appliquent actuellement sur la boutique {$name}.",
            ];
        }

        $contact = [];
        if ($shop->phone) {
            $contact[] = 'téléphone '.$shop->phone;
        }
        if ($shop->email) {
            $contact[] = 'e-mail '.$shop->email;
        }
        $pool[] = [
            'question' => 'Comment vous contacter ?',
            'answer' => $contact !== []
                ? 'Vous pouvez joindre '.$name.' par '.implode(' ou ', $contact).'.'
                : "Utilisez le formulaire de contact ou WhatsApp sur la vitrine {$name}.",
        ];

        $pool[] = [
            'question' => 'Comment suivre ma commande ?',
            'answer' => 'Après votre achat, conservez votre numéro de commande et l’e-mail de confirmation. Vous pouvez nous le communiquer pour connaître le statut de livraison.',
        ];

        $pool[] = [
            'question' => 'Quels moyens de paiement acceptez-vous ?',
            'answer' => "Les moyens de paiement disponibles sont indiqués lors du passage de commande sur la vitrine {$name}.",
        ];

        return array_slice($pool, 0, $count);
    }
}
