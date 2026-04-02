<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;

class EcommerceProductAiImageController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    public function analyze(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasPermission('ecommerce.product.create')
                && !$user->hasPermission('ecommerce.product.manage')
                && !$user->hasPermission('module.ecommerce'))) {
            abort(403, 'Vous n\'avez pas la permission d\'analyser des produits.');
        }

        $tenantId = $this->resolveTenantIdForBilling($request);
        if ($tenantId === null || $tenantId === '') {
            abort(403, 'Impossible de déterminer le tenant pour la facturation. Sélectionnez une boutique.');
        }

        $this->featureLimitService->assertFeatureEnabled($tenantId, 'ecommerce.product.ai_vision');

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $apiKey = config('services.openai.api_key');
        if ($apiKey === null || $apiKey === '') {
            return response()->json([
                'message' => 'Clé OpenAI non configurée sur le serveur (OPENAI_API_KEY).',
            ], 503);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['image'];
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode($file->getContent());
        $dataUrl = 'data:'.$mime.';base64,'.$base64;

        $system = <<<'PROMPT'
Tu es un assistant pour une boutique en ligne. À partir de la photo d'un produit, réponds UNIQUEMENT par un objet JSON valide UTF-8, sans texte avant ou après.
Clés du JSON (toutes les chaînes peuvent être vides si inconnu) :
- "name" : titre court du produit pour la fiche (max 120 caractères)
- "description" : description HTML simple pour vendre le produit (2 à 4 phrases, <p>...</p> uniquement, pas de scripts)
- "taille" : taille ou pointure si visible (ex: "L", "42", "XL") ou chaîne vide
- "couleur" : couleur principale ou chaîne vide
- "weight" : poids approximatif en kg si déductible, sinon null
- "length", "width", "height" : dimensions en cm si déductibles, sinon null
- "unit" : une des valeurs : PIECE, CARTON, BOITE, LOT, KG, G, LITRE, ML, M, M2, CANETTE (déduis du type de produit)
Ne pas inventer de prix ni de marque si elles ne sont pas lisibles sur l'image ; reste prudent.
PROMPT;

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken((string) $apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => 'Analyse cette image de produit et remplis le JSON demandé.'],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $dataUrl,
                                        'detail' => 'low',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 1200,
                    'temperature' => 0.35,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Ecommerce product AI vision HTTP error', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erreur de connexion au service d\'analyse. Réessayez plus tard.',
            ], 502);
        }

        if (!$response->successful()) {
            Log::warning('Ecommerce product AI vision API error', ['body' => $response->body()]);

            return response()->json([
                'message' => 'Le service d\'analyse a refusé la requête.',
            ], 502);
        }

        $data = $response->json();
        $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        $parsed = $this->extractJsonObject($content);
        if ($parsed === null) {
            Log::warning('Ecommerce product AI vision parse failed', ['content' => $content]);

            return response()->json([
                'message' => 'Réponse IA illisible. Réessayez avec une autre photo.',
            ], 422);
        }

        return response()->json([
            'suggestion' => $this->normalizeSuggestion($parsed),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSuggestion(array $raw): array
    {
        $unit = strtoupper(trim((string) ($raw['unit'] ?? 'PIECE')));
        $allowed = ['PIECE', 'CARTON', 'BOITE', 'LOT', 'KG', 'G', 'LITRE', 'ML', 'M', 'M2', 'CANETTE'];
        if (!in_array($unit, $allowed, true)) {
            $unit = 'PIECE';
        }

        $num = static function ($v): ?float {
            if ($v === null || $v === '') {
                return null;
            }
            if (is_numeric($v)) {
                return (float) $v;
            }

            return null;
        };

        return [
            'name' => mb_substr(trim((string) ($raw['name'] ?? '')), 0, 255),
            'description' => trim((string) ($raw['description'] ?? '')),
            'taille' => mb_substr(trim((string) ($raw['taille'] ?? '')), 0, 100),
            'couleur' => mb_substr(trim((string) ($raw['couleur'] ?? '')), 0, 100),
            'weight' => $num($raw['weight'] ?? null),
            'length' => $num($raw['length'] ?? null),
            'width' => $num($raw['width'] ?? null),
            'height' => $num($raw['height'] ?? null),
            'unit' => $unit,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJsonObject(string $content): ?array
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $trimmed, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function resolveTenantIdForBilling(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        if ($user->tenant_id !== null && $user->tenant_id !== '') {
            return (string) $user->tenant_id;
        }

        $shopId = $user->shop_id ?? null;
        if (($shopId === null || $shopId === '') && method_exists($user, 'isRoot') && $user->isRoot()) {
            $shopId = $request->session()->get('current_storefront_shop_id');
        }

        if ($shopId === null || $shopId === '' || !Schema::hasTable('shops')) {
            return null;
        }

        $tid = DB::table('shops')->where('id', $shopId)->value('tenant_id');

        return $tid !== null && $tid !== '' ? (string) $tid : null;
    }
}
