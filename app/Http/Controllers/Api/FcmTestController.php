<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationToken;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTestController extends Controller
{
    public function send(Request $request, NotificationService $notificationService): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $tokensCount = NotificationToken::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('revoked_at')
            ->count();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        if ($tokensCount <= 0) {
            return response()->json([
                'message' => 'Aucun token FCM enregistré pour cet utilisateur. Recharge la page, accepte les notifications, puis réessaie.',
                'tokens_count' => $tokensCount,
            ], 422);
        }

        $notificationService->sendToUser(
            (int) $user->id,
            (string) ($validated['title'] ?? 'Test notification'),
            (string) ($validated['body'] ?? 'Bonjour! Ceci est un test FCM.'),
            ['click_url' => '/dashboard']
        );

        return response()->json([
            'success' => true,
            'tokens_count' => $tokensCount,
        ]);
    }
}

