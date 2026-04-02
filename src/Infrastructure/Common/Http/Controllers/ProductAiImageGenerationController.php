<?php

namespace Src\Infrastructure\Common\Http\Controllers;

use App\Jobs\ProcessAiImageGenerationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Common\Models\AiImageGenerationRequest;

class ProductAiImageGenerationController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        $this->extendExecutionWindow(240);

        $tenantId = $this->resolveTenantIdForBilling($request);
        if ($tenantId === null || $tenantId === '') {
            abort(403, 'Impossible de déterminer le tenant pour la facturation.');
        }

        $this->featureLimitService->assertFeatureEnabled($tenantId, 'ai.product.image.generate');
        $this->featureLimitService->assertCanUseMonthlyFeature(
            $tenantId,
            'ai.product.image.generate',
            'la génération d’image IA'
        );

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'count' => 'nullable|integer|min:1|max:4',
            'async' => 'nullable|boolean',
        ]);

        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Clé OpenAI non configurée sur le serveur (OPENAI_API_KEY).',
            ], 503);
        }

        $title = trim((string) $validated['title']);
        $description = trim((string) ($validated['description'] ?? ''));
        $count = (int) ($validated['count'] ?? 1);
        $count = max(1, min(4, $count));
        $isAsync = (bool) ($validated['async'] ?? false);

        if ($isAsync && Schema::hasTable('ai_image_generation_requests')) {
            $row = AiImageGenerationRequest::query()->create([
                'tenant_id' => $tenantId,
                'feature_code' => 'ai.product.image.generate',
                'context' => 'product',
                'status' => 'pending',
                'count' => $count,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
            ]);

            ProcessAiImageGenerationRequest::dispatch($row->id);

            return response()->json([
                'request_id' => $row->id,
                'status' => $row->status,
            ], 202);
        }
        $prompt = "Génère une photo produit e-commerce propre sur fond neutre.\n"
            ."Titre: {$title}\n"
            ."Description: ".($description !== '' ? $description : 'non fournie')."\n"
            ."Contraintes: cadrage centré, rendu réaliste, pas de texte, pas de watermark, pas de logo externe.";

        $images = [];
        $rawError = '';
        for ($i = 1; $i <= $count; $i++) {
            $variantPrompt = $prompt . "\nVariation visuelle n°{$i}: angle/cadrage/lumière légèrement différente.";
            [$b64, $err] = $this->generateWithFallbackModels($apiKey, $variantPrompt);
            if ($b64 !== '') {
                $images[] = [
                    'image_data_url' => 'data:image/png;base64,'.$b64,
                    'mime_type' => 'image/png',
                    'file_name' => 'ai-product-'.$i.'-'.now()->format('YmdHis').'.png',
                ];
                continue;
            }
            $rawError = $err;
            break;
        }

        if (empty($images)) {
            return response()->json([
                'message' => $rawError !== '' ? $rawError : 'Réponse IA vide. Réessayez avec un autre titre/description.',
            ], 502);
        }

        $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.product.image.generate', count($images));

        return response()->json([
            // Compat: anciens écrans lisent ces champs.
            'image_data_url' => $images[0]['image_data_url'],
            'mime_type' => $images[0]['mime_type'],
            'file_name' => $images[0]['file_name'],
            // Nouveau: lot d'images.
            'images' => $images,
        ]);
    }

    public function status(Request $request, string $id): JsonResponse
    {
        if (!Schema::hasTable('ai_image_generation_requests')) {
            return response()->json(['message' => 'File IA asynchrone non initialisée. Lancez les migrations.'], 503);
        }

        $tenantId = $this->resolveTenantIdForBilling($request);
        if ($tenantId === null || $tenantId === '') {
            abort(403, 'Impossible de déterminer le tenant pour la facturation.');
        }

        $row = AiImageGenerationRequest::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('feature_code', 'ai.product.image.generate')
            ->first();

        if ($row === null) {
            return response()->json(['message' => 'Requête IA introuvable.'], 404);
        }

        $response = [
            'request_id' => $row->id,
            'status' => $row->status,
            'error_message' => $row->error_message,
        ];

        if ($row->status === 'completed') {
            $images = is_array($row->result_images) ? $row->result_images : [];
            $response['images'] = $images;
            if (!empty($images)) {
                $response['image_data_url'] = $images[0]['image_data_url'] ?? null;
                $response['mime_type'] = $images[0]['mime_type'] ?? 'image/png';
                $response['file_name'] = $images[0]['file_name'] ?? 'ai-product.png';
            }
        }

        return response()->json($response);
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
            $shopId = $request->session()->get('current_storefront_shop_id')
                ?? $request->session()->get('current_shop_id')
                ?? $request->session()->get('current_depot_id');
        }

        if ($shopId === null || $shopId === '' || !Schema::hasTable('shops')) {
            return null;
        }

        $tenantId = DB::table('shops')->where('id', $shopId)->value('tenant_id');

        return $tenantId !== null && $tenantId !== '' ? (string) $tenantId : null;
    }

    /**
     * @return array{0:string,1:string} [base64Image, userFriendlyError]
     */
    private function generateWithFallbackModels(string $apiKey, string $prompt): array
    {
        $attempts = [
            ['model' => 'gpt-image-1', 'response_format' => null],
            ['model' => 'dall-e-3', 'response_format' => 'b64_json'],
            ['model' => 'dall-e-3', 'response_format' => 'url'],
        ];

        $lastError = '';
        foreach ($attempts as $attempt) {
            try {
                $payload = [
                    'model' => $attempt['model'],
                    'prompt' => $prompt,
                    'size' => '1024x1024',
                ];
                if ($attempt['response_format'] !== null) {
                    $payload['response_format'] = $attempt['response_format'];
                }

                $response = Http::withToken($apiKey)
                    ->timeout(35)
                    ->post('https://api.openai.com/v1/images/generations', $payload);

                if (!$response->successful()) {
                    $lastError = (string) ($response->json('error.message') ?? $response->body() ?? '');
                    Log::warning('Product AI image generation API error', [
                        'model' => $attempt['model'],
                        'response_format' => $attempt['response_format'],
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                $b64 = (string) ($response->json('data.0.b64_json') ?? '');
                if ($b64 !== '') {
                    return [$b64, ''];
                }

                $url = (string) ($response->json('data.0.url') ?? '');
                if ($url !== '') {
                    $img = Http::timeout(30)->get($url);
                    if ($img->successful() && $img->body() !== '') {
                        return [base64_encode($img->body()), ''];
                    }
                    $lastError = 'Impossible de récupérer l’image générée.';
                    continue;
                }

                $lastError = 'Le service IA n’a renvoyé aucun média exploitable.';
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('Product AI image generation HTTP error', ['message' => $e->getMessage()]);
            }
        }

        if (str_contains(mb_strtolower($lastError), 'insufficient_quota')) {
            return ['', 'Quota API OpenAI insuffisant sur la clé serveur.'];
        }
        if (str_contains(mb_strtolower($lastError), 'model')) {
            return ['', 'Modèle image IA non disponible pour cette clé OpenAI.'];
        }

        return ['', $lastError !== '' ? 'Le service IA a refusé la génération de l’image.' : 'Le service IA est momentanément indisponible.'];
    }

    private function extendExecutionWindow(int $seconds): void
    {
        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit($seconds);
            }
            @ini_set('max_execution_time', (string) $seconds);
        } catch (\Throwable) {
            // Ignore: some environments disallow runtime override.
        }
    }
}

