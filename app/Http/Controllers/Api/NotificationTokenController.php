<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        Log::debug('FCM token store request', [
            'user_id' => (int) $user->id,
            'tenant_id' => $user->tenant_id ? (int) $user->tenant_id : null,
            'platform' => (string) ($data['platform'] ?? 'web'),
            'token_len' => strlen((string) $data['token']),
        ]);

        NotificationToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => (int) $user->id,
                'tenant_id' => $user->tenant_id ? (int) $user->tenant_id : null,
                'platform' => $data['platform'] ?? 'web',
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        $tokensCount = NotificationToken::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('revoked_at')
            ->count();

        return response()->json([
            'success' => true,
            'tokens_count' => $tokensCount,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        NotificationToken::query()
            ->where('token', $data['token'])
            ->where('user_id', (int) $user->id)
            ->update(['revoked_at' => now()]);

        return response()->json(['success' => true]);
    }
}

