<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Services\NotificationService;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Support\Models\SupportTicketModel;
use Src\Infrastructure\Support\Models\SupportTicketReplyModel;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Support/CreateTicket', [
            'enums' => [
                'priorities' => ['low', 'medium', 'high', 'critical'],
                'categories' => ['bug', 'request', 'incident', 'support'],
                'modules' => ['hardware', 'pharmacy', 'commerce', 'ecommerce', 'system'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:low,medium,high,critical',
            'category' => 'required|string|in:bug,request,incident,support',
            'module' => 'required|string|in:hardware,pharmacy,commerce,ecommerce,system',
            'attachment' => 'nullable|file|max:4096',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support/attachments', 'public');
        }

        SupportTicketModel::create([
            'tenant_id' => $user->tenant_id ?? null,
            'user_id' => $user->id,
            'assigned_to_user_id' => null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'module' => $validated['module'],
            'status' => 'open',
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('support.tickets.mine')
            ->with('success', 'Ticket créé avec succès.');
    }

    public function myTickets(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $tickets = SupportTicketModel::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(function (SupportTicketModel $ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'module' => $ticket->module,
                    'created_at' => $ticket->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Support/MyTickets', [
            'tickets' => $tickets,
        ]);
    }

    public function index(Request $request): Response
    {
        $query = SupportTicketModel::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        $tickets = $query->orderByDesc('created_at')->paginate(20);

        $tickets = $tickets->through(function (SupportTicketModel $ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'module' => $ticket->module,
                    'user_name' => $ticket->user?->name,
                    'assigned_to_name' => $ticket->assignedTo?->name,
                    'created_at' => $ticket->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Support/AllTickets', [
            'tickets' => $tickets,
        ]);
    }

    public function show(Request $request, SupportTicketModel $ticket): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->id !== $ticket->user_id && !$user->hasPermission('support.admin')) {
            abort(403);
        }

        $ticketData = [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'module' => $ticket->module,
            'attachment_url' => $ticket->attachment_path ? \Illuminate\Support\Facades\Storage::url($ticket->attachment_path) : null,
            'created_at' => $ticket->created_at?->toDateTimeString(),
            'user' => [
                'id' => $ticket->user?->id,
                'name' => $ticket->user?->name,
            ],
            'assigned_to' => $ticket->assignedTo ? [
                'id' => $ticket->assignedTo->id,
                'name' => $ticket->assignedTo->name,
            ] : null,
        ];

        $replies = $ticket->replies()
            ->orderBy('created_at')
            ->get()
            ->map(function (SupportTicketReplyModel $reply) {
                $user = $reply->user;
                return [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'attachment_url' => $reply->attachment_path ? \Illuminate\Support\Facades\Storage::url($reply->attachment_path) : null,
                    'created_at' => $reply->created_at?->toDateTimeString(),
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                    ] : null,
                ];
            });

        return Inertia::render('Support/TicketShow', [
            'ticket' => $ticketData,
            'replies' => $replies,
            'canManage' => $user->hasPermission('support.admin'),
        ]);
    }

    public function repliesJson(Request $request, SupportTicketModel $ticket): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->id !== $ticket->user_id && !$user->hasPermission('support.admin')) {
            abort(403);
        }

        $afterId = (int) ($request->query('after_id') ?? 0);

        $query = $ticket->replies()
            ->orderBy('created_at');

        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        }

        $replies = $query->limit(200)->get()->map(function (SupportTicketReplyModel $reply) {
            $u = $reply->user;
            return [
                'id' => (int) $reply->id,
                'message' => (string) $reply->message,
                'attachment_url' => $reply->attachment_path ? \Illuminate\Support\Facades\Storage::url($reply->attachment_path) : null,
                'created_at' => $reply->created_at?->toDateTimeString(),
                'user' => $u ? [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                ] : null,
            ];
        })->values();

        return response()->json([
            'ticket' => [
                'id' => (int) $ticket->id,
                'status' => (string) $ticket->status,
                'updated_at' => $ticket->updated_at?->toDateTimeString(),
            ],
            'replies' => $replies,
        ]);
    }

    public function reply(Request $request, SupportTicketModel $ticket, NotificationService $notificationService): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->id !== $ticket->user_id && !$user->hasPermission('support.admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:4096',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support/attachments', 'public');
        }

        SupportTicketReplyModel::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);

        if ($ticket->status === 'open') {
            $ticket->status = 'in_progress';
            $ticket->save();
        }

        // Realtime notification via Firebase (FCM)
        try {
            $clickUrl = '/support/tickets/' . $ticket->id;
            $data = [
                'type' => 'support.ticket.reply',
                'ticket_id' => (string) $ticket->id,
                'click_url' => $clickUrl,
            ];

            $senderIsSupport = method_exists($user, 'hasPermission') && $user->hasPermission('support.admin');

            if ($senderIsSupport) {
                // Admin replied -> notify ticket owner
                if ((int) $ticket->user_id !== (int) $user->id) {
                    $notificationService->sendToUser(
                        (int) $ticket->user_id,
                        'Réponse du support',
                        'Vous avez reçu une réponse sur votre ticket #' . $ticket->id . '.',
                        $data
                    );
                }
            } else {
                // User replied -> notify assigned support agent if any, otherwise all support admins
                $targets = [];
                if (!empty($ticket->assigned_to_user_id)) {
                    $targets[] = (int) $ticket->assigned_to_user_id;
                } else {
                    $targets = DB::table('user_role')
                        ->join('role_permission', 'user_role.role_id', '=', 'role_permission.role_id')
                        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                        ->where('permissions.code', 'support.admin')
                        ->distinct()
                        ->pluck('user_role.user_id')
                        ->map(fn ($id) => (int) $id)
                        ->toArray();
                }

                $targets = array_values(array_unique(array_filter($targets, fn ($id) => (int) $id !== (int) $user->id)));

                foreach ($targets as $adminUserId) {
                    $notificationService->sendToUser(
                        (int) $adminUserId,
                        'Nouveau message support',
                        'Nouveau message sur le ticket #' . $ticket->id . '.',
                        $data
                    );
                }
            }
        } catch (\Throwable $e) {
            // never break ticket reply
        }

        return redirect()->route('support.tickets.show', $ticket->id)
            ->with('success', 'Réponse ajoutée au ticket.');
    }

    public function assign(Request $request, SupportTicketModel $ticket): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('support.admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'assigned_to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $ticket->assigned_to_user_id = $validated['assigned_to_user_id'] ?? null;
        $ticket->save();

        return back()->with('success', 'Ticket mis à jour.');
    }

    public function updateStatus(Request $request, SupportTicketModel $ticket): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || !$user->hasPermission('support.admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved,closed',
        ]);

        $ticket->status = $validated['status'];
        $ticket->save();

        return back()->with('success', 'Statut du ticket mis à jour.');
    }

    public function incidents(Request $request): Response
    {
        // Pour l’instant, on réutilise les tickets de type incident comme "historique des incidents"
        $incidents = SupportTicketModel::where('category', 'incident')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (SupportTicketModel $ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'severity' => $ticket->priority,
                    'module' => $ticket->module,
                    'created_at' => $ticket->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Support/Incidents', [
            'incidents' => $incidents,
        ]);
    }
}

