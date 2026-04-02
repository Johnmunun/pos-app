<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Common\Models\AiImageGenerationRequest;

class ProcessAiImageGenerationRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 1;

    public function __construct(
        public string $requestId,
    ) {
    }

    public function handle(FeatureLimitService $featureLimitService): void
    {
        $request = AiImageGenerationRequest::query()->find($this->requestId);
        if ($request === null || in_array($request->status, ['completed', 'failed'], true)) {
            return;
        }

        $request->status = 'processing';
        $request->started_at = now();
        $request->save();

        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            $request->status = 'failed';
            $request->error_message = 'Clé OpenAI non configurée sur le serveur (OPENAI_API_KEY).';
            $request->finished_at = now();
            $request->save();
            return;
        }

        $count = max(1, min(4, (int) $request->count));
        $prompt = $request->context === 'media'
            ? "Génère une image de média CMS e-commerce propre et esthétique.\nTitre: {$request->title}\nDescription: ".(($request->description ?? '') !== '' ? $request->description : 'non fournie')."\nContraintes: rendu réaliste ou marketing, sans texte lisible, sans watermark, sans logo externe."
            : "Génère une photo produit e-commerce propre sur fond neutre.\nTitre: {$request->title}\nDescription: ".(($request->description ?? '') !== '' ? $request->description : 'non fournie')."\nContraintes: cadrage centré, rendu réaliste, pas de texte, pas de watermark, pas de logo externe.";

        $images = [];
        $rawError = '';
        for ($i = 1; $i <= $count; $i++) {
            $variantPrompt = $prompt . "\nVariation visuelle n°{$i}: angle/cadrage/lumière légèrement différente.";
            [$b64, $err] = $this->generateWithFallbackModels($apiKey, $variantPrompt);
            if ($b64 !== '') {
                $prefix = $request->context === 'media' ? 'cms-media-ai' : 'ai-product';
                $images[] = [
                    'image_data_url' => 'data:image/png;base64,'.$b64,
                    'mime_type' => 'image/png',
                    'file_name' => $prefix.'-'.$i.'-'.now()->format('YmdHis').'.png',
                ];
                continue;
            }
            $rawError = $err;
            break;
        }

        if (empty($images)) {
            $request->status = 'failed';
            $request->error_message = $rawError !== '' ? $rawError : 'Réponse IA vide. Réessayez avec un autre titre/description.';
            $request->finished_at = now();
            $request->save();
            return;
        }

        $featureLimitService->recordFeatureUsage((string) $request->tenant_id, (string) $request->feature_code, count($images));

        $request->status = 'completed';
        $request->result_images = $images;
        $request->error_message = null;
        $request->finished_at = now();
        $request->save();
    }

    /**
     * @return array{0:string,1:string}
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
                    continue;
                }

                $b64 = (string) ($response->json('data.0.b64_json') ?? '');
                if ($b64 !== '') {
                    return [$b64, ''];
                }

                $url = (string) ($response->json('data.0.url') ?? '');
                if ($url !== '') {
                    $img = Http::timeout(20)->get($url);
                    if ($img->successful() && $img->body() !== '') {
                        return [base64_encode($img->body()), ''];
                    }
                    $lastError = 'Impossible de récupérer l’image générée.';
                    continue;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('Async AI image generation failed', ['message' => $e->getMessage()]);
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
}
