<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Infrastructure\Support\Models\SupportChatConversationModel;
use Src\Infrastructure\Support\Models\SupportChatMessageModel;
use Src\Infrastructure\Support\Models\SupportChatPresenceModel;

class SupportChatController extends Controller
{
    private function chatMessageColumnSupport(): array
    {
        static $support = null;
        if ($support !== null) {
            return $support;
        }

        $support = [
            'attachment_path' => Schema::hasColumn('support_chat_messages', 'attachment_path'),
            'attachment_mime' => Schema::hasColumn('support_chat_messages', 'attachment_mime'),
            'pinned_at' => Schema::hasColumn('support_chat_messages', 'pinned_at'),
            'pinned_by_user_id' => Schema::hasColumn('support_chat_messages', 'pinned_by_user_id'),
        ];

        return $support;
    }

    private function mapMessagePayload(SupportChatMessageModel $m): array
    {
        return [
            'id' => (int) $m->id,
            'sender_user_id' => $m->sender_user_id !== null ? (int) $m->sender_user_id : null,
            'sender_type' => (string) ($m->sender_type ?? 'user'),
            'sender_name' => $m->sender ? (string) $m->sender->getAttribute('name') : null,
            'message' => (string) $m->message,
            'attachment_url' => $m->attachment_path ? Storage::url($m->attachment_path) : null,
            'attachment_mime' => $m->attachment_mime ? (string) $m->attachment_mime : null,
            'pinned_at' => $m->pinned_at?->toDateTimeString(),
            'pinned_by_user_id' => $m->pinned_by_user_id !== null ? (int) $m->pinned_by_user_id : null,
            'created_at' => $m->created_at?->toDateTimeString(),
        ];
    }

    private function notifyChatRecipients(
        SupportChatConversationModel $conversation,
        int $senderUserId,
        bool $senderIsSupport,
        NotificationService $notificationService
    ): void {
        $data = [
            'type' => 'support.chat.message',
            'conversation_id' => (string) $conversation->id,
            'click_url' => '/support/admin/chat',
        ];

        if ($senderIsSupport) {
            if ((int) $conversation->user_id > 0 && (int) $conversation->user_id !== $senderUserId) {
                $notificationService->sendToUser(
                    (int) $conversation->user_id,
                    'Nouveau message du support',
                    'Vous avez reçu une réponse dans le chat support.',
                    $data
                );
            }
            return;
        }

        $targets = [];
        if (!empty($conversation->assigned_to_user_id)) {
            $targets[] = (int) $conversation->assigned_to_user_id;
        } else {
            $targets = DB::table('user_role')
                ->join('role_permission', 'user_role.role_id', '=', 'role_permission.role_id')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->whereIn('permissions.code', ['support.admin', 'crm.dashboard.view'])
                ->distinct()
                ->pluck('user_role.user_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        $targets = array_values(array_unique(array_filter($targets, fn ($id) => (int) $id !== $senderUserId)));
        foreach ($targets as $targetUserId) {
            $notificationService->sendToUser(
                (int) $targetUserId,
                'Nouveau message support',
                'Un client vient d\'envoyer un message.',
                $data
            );
        }
    }

    private function getOrCreateGuestKey(Request $request): string
    {
        $key = (string) ($request->session()->get('support_chat_guest_key') ?? '');
        if ($key !== '') {
            return $key;
        }
        $key = bin2hex(random_bytes(16));
        $request->session()->put('support_chat_guest_key', $key);
        return $key;
    }

    private function canAccessConversation(Request $request, SupportChatConversationModel $conversation): bool
    {
        $user = $request->user();
        if ($user) {
            $isSupport = method_exists($user, 'hasPermission')
                && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
            if ($isSupport) return true;
            return (int) $conversation->user_id === (int) $user->id;
        }
        $guestKey = $this->getOrCreateGuestKey($request);
        return !empty($conversation->guest_key) && hash_equals((string) $conversation->guest_key, (string) $guestKey);
    }

    public function adminIndex(Request $request): InertiaResponse
    {
        $user = $request->user();
        $can = $user && method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$can) {
            abort(403);
        }

        // Mark support agent online
        $this->heartbeat($request, true);

        $lastMessageIdsByConversationId = SupportChatMessageModel::query()
            ->select(['conversation_id', DB::raw('MAX(id) as last_message_id')])
            ->groupBy('conversation_id')
            ->pluck('last_message_id', 'conversation_id')
            ->toArray();
        $lastMessagesById = SupportChatMessageModel::query()
            ->whereIn('id', array_values($lastMessageIdsByConversationId))
            ->get()
            ->keyBy('id');

        $conversations = SupportChatConversationModel::query()
            ->with(['user', 'assignedTo'])
            ->orderByDesc('last_message_at')
            ->limit(200)
            ->get()
            ->map(function (SupportChatConversationModel $c) use ($lastMessageIdsByConversationId, $lastMessagesById) {
                $isGuest = empty($c->user_id);
                $lastMessage = null;
                $lastMessageId = (int) ($lastMessageIdsByConversationId[$c->id] ?? 0);
                if ($lastMessageId > 0) {
                    $lastMessage = $lastMessagesById->get($lastMessageId);
                }
                return [
                    'id' => (int) $c->id,
                    'status' => (string) $c->status,
                    'last_message_at' => $c->last_message_at?->toDateTimeString(),
                    'last_message_id' => $lastMessageId,
                    'last_message_preview' => $lastMessage ? (string) $lastMessage->message : null,
                    'user' => $isGuest ? [
                        'id' => null,
                        'name' => (string) ($c->guest_name ?: 'Visiteur'),
                        'phone' => $c->guest_phone ? (string) $c->guest_phone : null,
                        'email' => null,
                        'type' => 'guest',
                    ] : [
                        'id' => (int) ($c->user?->getAttribute('id') ?? 0),
                        'name' => (string) ($c->user?->getAttribute('name') ?? 'Client'),
                        'phone' => (string) ($c->user?->getAttribute('phone') ?? ''),
                        'email' => (string) ($c->user?->getAttribute('email') ?? ''),
                        'type' => 'user',
                    ],
                    'assigned_to' => $c->assignedTo ? [
                        'id' => (int) $c->assignedTo->getAttribute('id'),
                        'name' => (string) $c->assignedTo->getAttribute('name'),
                    ] : null,
                ];
            })
            ->toArray();

        return Inertia::render('Support/AdminChat', [
            'conversations' => $conversations,
        ]);
    }

    public function ensureConversation(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->heartbeat($request, false);

        $conversation = SupportChatConversationModel::query()
            ->where('user_id', (int) $user->id)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        if (!$conversation) {
            $conversation = SupportChatConversationModel::create([
                'tenant_id' => $user->tenant_id ?? null,
                'user_id' => (int) $user->id,
                'assigned_to_user_id' => null,
                'status' => 'open',
                'last_message_at' => null,
            ]);
        }

        return response()->json([
            'conversation' => [
                'id' => (int) $conversation->id,
                'status' => (string) $conversation->status,
            ],
        ]);
    }

    public function ensureGuestConversation(Request $request): JsonResponse
    {
        $guestKey = $this->getOrCreateGuestKey($request);
        $validated = $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
        ]);

        $conversation = SupportChatConversationModel::query()
            ->where('user_id', 0)
            ->where('guest_key', $guestKey)
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        if (!$conversation) {
            $conversation = SupportChatConversationModel::create([
                'tenant_id' => null,
                'user_id' => 0, // guest conversation (DB requires a value)
                'guest_key' => $guestKey,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'assigned_to_user_id' => null,
                'status' => 'open',
                'last_message_at' => null,
            ]);
        } else {
            $shouldSave = false;
            if (!empty($validated['guest_name']) && empty($conversation->guest_name)) {
                $conversation->guest_name = $validated['guest_name'];
                $shouldSave = true;
            }
            if (!empty($validated['guest_phone']) && empty($conversation->guest_phone)) {
                $conversation->guest_phone = $validated['guest_phone'];
                $shouldSave = true;
            }
            if ($shouldSave) $conversation->save();
        }

        return response()->json([
            'conversation' => [
                'id' => (int) $conversation->id,
                'status' => (string) $conversation->status,
            ],
        ]);
    }

    public function messages(Request $request, SupportChatConversationModel $conversation): JsonResponse
    {
        if (!$this->canAccessConversation($request, $conversation)) {
            abort(403);
        }

        $afterId = (int) ($request->query('after_id') ?? 0);

        $q = SupportChatMessageModel::query()
            ->where('conversation_id', (int) $conversation->id)
            ->orderBy('id');
        if ($afterId > 0) {
            $q->where('id', '>', $afterId);
        }

        $messages = $q->limit(200)->get()->map(fn (SupportChatMessageModel $m) => $this->mapMessagePayload($m))->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function send(
        Request $request,
        SupportChatConversationModel $conversation,
        NotificationService $notificationService
    ): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $isSupport = method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$this->canAccessConversation($request, $conversation)) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096', 'required_without:message'],
        ]);

        $columnSupport = $this->chatMessageColumnSupport();
        if ($request->hasFile('attachment') && !($columnSupport['attachment_path'] ?? false)) {
            return response()->json([
                'message' => 'La base de donnees doit etre migree pour envoyer des images.',
            ], 409);
        }

        $attachmentPath = null;
        $attachmentMime = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('support/chat', 'public');
            $attachmentMime = $file->getClientMimeType();
        }

        $msg = SupportChatMessageModel::create([
            ...[
                'conversation_id' => (int) $conversation->id,
                'sender_user_id' => (int) $user->id,
                'sender_type' => $isSupport ? 'support' : 'user',
                'message' => (string) ($data['message'] ?? ''),
            ],
            ...(($columnSupport['attachment_path'] ?? false) ? ['attachment_path' => $attachmentPath] : []),
            ...(($columnSupport['attachment_mime'] ?? false) ? ['attachment_mime' => $attachmentMime] : []),
        ]);

        $conversation->last_message_at = now();
        if ($isSupport && empty($conversation->assigned_to_user_id)) {
            $conversation->assigned_to_user_id = (int) $user->id;
        }
        $conversation->save();

        // Notify SSE listeners quickly by touching a lightweight row
        DB::table('support_chat_conversations')->where('id', (int) $conversation->id)->update(['updated_at' => now()]);
        $this->heartbeat($request, $isSupport);

        try {
            $this->notifyChatRecipients($conversation, (int) $user->id, $isSupport, $notificationService);
        } catch (\Throwable $e) {
            // Never block chat send on push failures
        }

        return response()->json([
            'message' => $this->mapMessagePayload($msg),
        ]);
    }

    public function sendGuest(
        Request $request,
        SupportChatConversationModel $conversation,
        NotificationService $notificationService
    ): JsonResponse
    {
        if (!$this->canAccessConversation($request, $conversation)) {
            abort(403);
        }
        if ((int) $conversation->user_id !== 0) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096', 'required_without:message'],
        ]);

        $columnSupport = $this->chatMessageColumnSupport();
        if ($request->hasFile('attachment') && !($columnSupport['attachment_path'] ?? false)) {
            return response()->json([
                'message' => 'La base de donnees doit etre migree pour envoyer des images.',
            ], 409);
        }

        $attachmentPath = null;
        $attachmentMime = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('support/chat', 'public');
            $attachmentMime = $file->getClientMimeType();
        }

        $msg = SupportChatMessageModel::create([
            ...[
                'conversation_id' => (int) $conversation->id,
                'sender_user_id' => null,
                'sender_type' => 'guest',
                'message' => (string) ($data['message'] ?? ''),
            ],
            ...(($columnSupport['attachment_path'] ?? false) ? ['attachment_path' => $attachmentPath] : []),
            ...(($columnSupport['attachment_mime'] ?? false) ? ['attachment_mime' => $attachmentMime] : []),
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        DB::table('support_chat_conversations')->where('id', (int) $conversation->id)->update(['updated_at' => now()]);
        try {
            $this->notifyChatRecipients($conversation, 0, false, $notificationService);
        } catch (\Throwable $e) {
            // Never block chat send on push failures
        }

        return response()->json([
            'message' => $this->mapMessagePayload($msg),
        ]);
    }

    public function pinMessage(
        Request $request,
        SupportChatConversationModel $conversation,
        SupportChatMessageModel $message
    ): JsonResponse {
        $user = $request->user();
        $isSupport = $user && method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$isSupport || !$this->canAccessConversation($request, $conversation)) {
            abort(403);
        }
        if ((int) $message->conversation_id !== (int) $conversation->id) {
            abort(404);
        }
        if (!($this->chatMessageColumnSupport()['pinned_at'] ?? false)) {
            return response()->json([
                'message' => 'La base de donnees doit etre migree pour l\'epinglage.',
            ], 409);
        }

        $pin = (bool) $request->boolean('pin', true);
        if ($pin) {
            SupportChatMessageModel::query()
                ->where('conversation_id', (int) $conversation->id)
                ->whereNotNull('pinned_at')
                ->update(['pinned_at' => null, 'pinned_by_user_id' => null]);
            $message->pinned_at = now();
            $message->pinned_by_user_id = (int) $user->id;
            $message->save();
        } else {
            $message->pinned_at = null;
            $message->pinned_by_user_id = null;
            $message->save();
        }

        return response()->json([
            'message' => $this->mapMessagePayload($message),
        ]);
    }

    public function updateConversationStatus(Request $request, SupportChatConversationModel $conversation): JsonResponse
    {
        $user = $request->user();
        $isSupport = $user && method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$isSupport) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:open,closed'],
        ]);
        $conversation->status = (string) $data['status'];
        $conversation->save();

        return response()->json([
            'conversation' => [
                'id' => (int) $conversation->id,
                'status' => (string) $conversation->status,
            ],
        ]);
    }

    public function supportAgents(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSupport = $user && method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$isSupport) {
            abort(403);
        }

        $agents = DB::table('users')
            ->join('user_role', 'users.id', '=', 'user_role.user_id')
            ->join('role_permission', 'user_role.role_id', '=', 'role_permission.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->whereIn('permissions.code', ['support.admin', 'crm.dashboard.view'])
            ->distinct()
            ->select(['users.id', 'users.name'])
            ->orderBy('users.name')
            ->limit(150)
            ->get()
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => (string) $u->name])
            ->values();

        return response()->json(['agents' => $agents]);
    }

    public function assignConversation(Request $request, SupportChatConversationModel $conversation): JsonResponse
    {
        $user = $request->user();
        $isSupport = $user && method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view'));
        if (!$isSupport) {
            abort(403);
        }

        $data = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $conversation->assigned_to_user_id = $data['assigned_to_user_id'] ?? null;
        $conversation->save();

        $assignedName = null;
        if (!empty($conversation->assigned_to_user_id)) {
            $assignedName = DB::table('users')
                ->where('id', (int) $conversation->assigned_to_user_id)
                ->value('name');
        }

        return response()->json([
            'conversation' => [
                'id' => (int) $conversation->id,
                'assigned_to' => $conversation->assigned_to_user_id ? [
                    'id' => (int) $conversation->assigned_to_user_id,
                    'name' => (string) ($assignedName ?? 'Agent'),
                ] : null,
            ],
        ]);
    }

    public function heartbeat(Request $request, bool $forceSupport = false): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $isSupport = $forceSupport || (method_exists($user, 'hasPermission')
            && ($user->hasPermission('support.admin') || $user->hasPermission('crm.dashboard.view')));

        SupportChatPresenceModel::query()->updateOrCreate(
            ['user_id' => (int) $user->id],
            [
                'role' => $isSupport ? 'support' : 'customer',
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function guestHeartbeat(Request $request): JsonResponse
    {
        // Only used for "support online" indicator on landing
        $this->getOrCreateGuestKey($request);
        return response()->json(['success' => true]);
    }

    public function supportOnline(Request $request): JsonResponse
    {
        $threshold = now()->subSeconds(45);
        $online = SupportChatPresenceModel::query()
            ->where('role', 'support')
            ->where('last_seen_at', '>=', $threshold)
            ->exists();

        return response()->json([
            'online' => (bool) $online,
        ]);
    }

    public function stream(Request $request, SupportChatConversationModel $conversation): StreamedResponse
    {
        if (!$this->canAccessConversation($request, $conversation)) {
            abort(403);
        }

        // Important: release Laravel session lock for long-lived SSE response.
        // Otherwise, concurrent requests from same user (send message, pin, etc.)
        // can stay pending until stream finishes.
        if ($request->hasSession()) {
            try {
                $request->session()->save();
            } catch (\Throwable $e) {
                // noop
            }
        }

        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');

        $lastId = (int) ($request->query('after_id') ?? 0);
        // EventSource reconnect sends Last-Event-ID; use it to avoid replaying old messages.
        $lastEventId = (int) ($request->header('Last-Event-ID') ?? 0);
        if ($lastEventId > $lastId) {
            $lastId = $lastEventId;
        }

        /** @var StreamedResponse $resp */
        $resp = response()->stream(function () use ($conversation, $lastId) {
            $start = microtime(true);
            $maxSeconds = 55; // keep-alive friendly for proxies
            $cursor = $lastId;

            while (microtime(true) - $start < $maxSeconds) {
                $messages = SupportChatMessageModel::query()
                    ->where('conversation_id', (int) $conversation->id)
                    ->where('id', '>', $cursor)
                    ->orderBy('id')
                    ->limit(50)
                    ->get();

                foreach ($messages as $m) {
                    $cursor = (int) $m->id;
                    $payload = json_encode($this->mapMessagePayload($m), JSON_UNESCAPED_UNICODE);

                    echo "event: message\n";
                    echo "id: {$cursor}\n";
                    echo "data: {$payload}\n\n";
                    @ob_flush();
                    @flush();
                }

                // keep-alive ping
                echo "event: ping\n";
                echo "data: {}\n\n";
                @ob_flush();
                @flush();

                usleep(900000); // ~0.9s (not polling endpoints, single open stream)
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
        return $resp;
    }
}

