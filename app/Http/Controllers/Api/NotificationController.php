<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        // Pour l'instant, on renvoie les dernières notifications globales (ROOT)
        // On pourra filtrer par user_id / tenant_id plus tard.
        $notifications = AppNotification::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (AppNotification $n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'type' => $n->type,
                    'created_at' => $n->created_at?->toDateTimeString(),
                    'read_at' => $n->read_at?->toDateTimeString(),
                ];
            })
            ->toArray();

        $unreadCount = AppNotification::whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
}

