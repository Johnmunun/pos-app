<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Support\Models\SupportTicketModel;
use Src\Infrastructure\Support\Models\SupportTicketReplyModel;

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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $tickets */
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
            'attachment_url' => $ticket->attachment_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->attachment_path) : null,
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
                    'attachment_url' => $reply->attachment_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($reply->attachment_path) : null,
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

    public function reply(Request $request, SupportTicketModel $ticket): RedirectResponse
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

