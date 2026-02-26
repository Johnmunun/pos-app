<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Models\PharmacyAssistantSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Src\Application\Pharmacy\Services\PharmacyAssistantContextService;
use Src\Application\Pharmacy\Services\VoiceSensitiveDetector;

class PharmacyVoiceController
{
    private const DEFAULT_MAX_VOICE_REQUESTS_PER_DAY = 30;
    private const TRANSCRIBE_CACHE_TTL = 60;
    private const MAX_AUDIO_SIZE_MB = 5;
    private const ALLOWED_MIMES = ['audio/webm', 'audio/wav', 'audio/mpeg', 'audio/mp4', 'audio/mp3'];

    public function __construct(
        private PharmacyAssistantContextService $contextService,
        private VoiceSensitiveDetector $sensitiveDetector
    ) {}

    /**
     * POST /pharmacy/api/voice/transcribe
     * Body: multipart avec fichier "audio"
     * Retour: { "transcript": "...", "language": "fr" }
     */
    public function transcribe(Request $request): JsonResponse
    {
        $shopId = $this->resolveShopId($request);
        if (!$shopId) {
            return response()->json(['message' => 'Boutique non associée.'], 403);
        }
        if (!$this->checkVoiceQuota($shopId)) {
            $max = config('pharmacy_assistant.voice_max_requests_per_day', self::DEFAULT_MAX_VOICE_REQUESTS_PER_DAY);
            return response()->json(['message' => "Quota vocal quotidien atteint ({$max} requêtes / jour)."], 429);
        }

        $request->validate([
            'audio' => 'required|file|max:' . (self::MAX_AUDIO_SIZE_MB * 1024) . '|mimes:webm,wav,mp3,m4a,mpga,mpeg,mp4',
        ]);
        $file = $request->file('audio');
        if (!$file->isValid()) {
            return response()->json(['message' => 'Fichier audio invalide.'], 422);
        }
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES, true) && !str_starts_with($mime, 'audio/')) {
            return response()->json(['message' => 'Type audio non autorisé (webm, wav, mp3).'], 422);
        }

        $cacheKey = 'voice_transcribe:' . md5_file($file->getRealPath());
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $this->incrementVoiceQuota($shopId);
            return response()->json($cached);
        }

        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            return response()->json(['message' => 'Transcription non configurée (OpenAI).'], 503);
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->attach('file', $file->get(), $file->getClientOriginalName())
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'response_format' => 'verbose_json',
                    'language' => null,
                ]);
            if (!$response->successful()) {
                Log::warning('Voice transcribe error', ['body' => $response->body()]);
                return response()->json(['message' => 'Erreur de transcription.'], 502);
            }
            $data = $response->json();
            $transcript = trim($data['text'] ?? '');
            $language = $data['language'] ?? 'fr';
            $result = ['transcript' => $transcript, 'language' => $language];
            Cache::put($cacheKey, $result, self::TRANSCRIBE_CACHE_TTL);
            $this->incrementVoiceQuota($shopId);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('Voice transcribe exception', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur de transcription.'], 502);
        }
    }

    /**
     * POST /pharmacy/api/voice/speak
     * Body: { "text": "...", "voice": "female", "speed": 1.0 }
     * Retour: { "audio_url": "/storage/tts/..." } ou 403 si contenu sensible
     */
    public function speak(Request $request): JsonResponse
    {
        $shopId = $this->resolveShopId($request);
        if (!$shopId) {
            return response()->json(['message' => 'Boutique non associée.'], 403);
        }
        if (!$this->checkVoiceQuota($shopId)) {
            return response()->json(['message' => 'Quota vocal quotidien atteint.'], 429);
        }

        $request->validate([
            'text' => 'required|string|max:2000',
            'voice' => 'nullable|string|in:male,female,alloy,echo,fable,onyx,nova,shimmer',
            'speed' => 'nullable|numeric|min:0.5|max:2',
        ]);
        $text = trim($request->input('text'));
        if ($text === '') {
            return response()->json(['message' => 'Texte vide.'], 422);
        }
        if ($this->sensitiveDetector->isSensitive($text)) {
            return response()->json([
                'message' => 'Réponse non disponible en audio (données sensibles).',
                'audio_url' => null,
                'sensitive' => true,
            ], 200);
        }

        $settings = $this->getSettings($shopId);
        if (!$settings['voice_enabled']) {
            return response()->json(['message' => 'Voix désactivée.', 'audio_url' => null], 200);
        }

        $voice = $request->input('voice') ?? $settings['voice_type'];
        $speed = (float) ($request->input('speed') ?? $settings['voice_speed']);
        $openAiVoice = $this->mapVoiceToOpenAI($voice);

        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            return response()->json(['message' => 'Synthèse vocale non configurée.'], 503);
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->accept('audio/mpeg')
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'tts-1-hd',
                    'input' => $text,
                    'voice' => $openAiVoice,
                    'speed' => $speed,
                ]);
            if (!$response->successful()) {
                Log::warning('Voice TTS error', ['body' => $response->body()]);
                return response()->json(['message' => 'Erreur de synthèse vocale.'], 502);
            }
            $body = $response->body();
            if (strlen($body) < 500) {
                Log::warning('Voice TTS short response', ['len' => strlen($body)]);
                return response()->json(['message' => 'Erreur de synthèse vocale.'], 502);
            }
            $filename = 'tts/' . uniqid('response_', true) . '.mp3';
            Storage::disk('public')->put($filename, $body);
            $this->incrementVoiceQuota($shopId);
            $this->scheduleTtsCleanup($filename);
            /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
            $publicDisk = Storage::disk('public');
            $url = $publicDisk->url($filename);
            return response()->json(['audio_url' => $url]);
        } catch (\Throwable $e) {
            Log::warning('Voice TTS exception', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur de synthèse vocale.'], 502);
        }
    }

    /**
     * GET /pharmacy/api/voice/settings
     */
    public function settings(Request $request): JsonResponse
    {
        $shopId = $this->resolveShopId($request);
        if (!$shopId) {
            return response()->json(['message' => 'Boutique non associée.'], 403);
        }
        return response()->json($this->getSettings($shopId));
    }

    /**
     * PUT /pharmacy/api/voice/settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $shopId = $this->resolveShopId($request);
        if (!$shopId) {
            return response()->json(['message' => 'Boutique non associée.'], 403);
        }
        $request->validate([
            'voice_enabled' => 'nullable|boolean',
            'voice_type' => 'nullable|string|in:male,female',
            'voice_speed' => 'nullable|numeric|min:0.5|max:2',
            'auto_play' => 'nullable|boolean',
            'language' => 'nullable|string|in:fr,en,auto',
        ]);
        $setting = PharmacyAssistantSetting::firstOrNew(['shop_id' => $shopId], PharmacyAssistantSetting::defaults());
        $setting->shop_id = (int) $shopId;
        foreach (['voice_enabled', 'voice_type', 'voice_speed', 'auto_play', 'language'] as $key) {
            if ($request->has($key)) {
                $setting->$key = $request->input($key);
            }
        }
        $setting->save();
        return response()->json($this->getSettings($shopId));
    }

    private function resolveShopId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }
        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }
        return $user->tenant_id ? (string) $user->tenant_id : null;
    }

    private function getSettings(string $shopId): array
    {
        $s = PharmacyAssistantSetting::where('shop_id', $shopId)->first();
        $defaults = PharmacyAssistantSetting::defaults();
        if (!$s) {
            return $defaults;
        }
        return [
            'voice_enabled' => $s->voice_enabled,
            'voice_type' => $s->voice_type,
            'voice_speed' => (float) $s->voice_speed,
            'auto_play' => $s->auto_play,
            'language' => $s->language,
        ];
    }

    private function mapVoiceToOpenAI(string $voice): string
    {
        return match ($voice) {
            'male' => 'onyx',
            'female' => 'nova',
            'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer' => $voice,
            default => 'nova',
        };
    }

    private function checkVoiceQuota(string $shopId): bool
    {
        $max = config('pharmacy_assistant.voice_max_requests_per_day', self::DEFAULT_MAX_VOICE_REQUESTS_PER_DAY);
        $key = 'voice_requests:' . $shopId . ':' . now()->format('Y-m-d');
        return (int) Cache::get($key, 0) < $max;
    }

    private function incrementVoiceQuota(string $shopId): void
    {
        $key = 'voice_requests:' . $shopId . ':' . now()->format('Y-m-d');
        Cache::add($key, 0, now()->endOfDay());
        Cache::increment($key);
    }

    private function scheduleTtsCleanup(string $path): void
    {
        \App\Jobs\CleanupTtsFileJob::dispatch($path)->delay(now()->addMinutes(10));
    }
}
