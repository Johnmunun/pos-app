<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Support\Http\Controllers\SupportTicketController;
use Src\Infrastructure\Support\Http\Controllers\SupportChatController;
use Src\Infrastructure\Support\Http\Controllers\SupportFaqController;
use Src\Infrastructure\Support\Http\Controllers\SupportContactController;
use Src\Infrastructure\Support\Http\Controllers\SupportStatusController;

Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
    // Chat - client
    Route::post('/chat/conversation', [SupportChatController::class, 'ensureConversation'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.conversation');

    Route::get('/chat/support-online', [SupportChatController::class, 'supportOnline'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.support-online');

    Route::post('/chat/heartbeat', [SupportChatController::class, 'heartbeat'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.heartbeat');

    Route::get('/chat/{conversation}/messages', [SupportChatController::class, 'messages'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.messages');

    Route::post('/chat/{conversation}/messages', [SupportChatController::class, 'send'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.send');

    Route::post('/chat/{conversation}/messages/{message}/pin', [SupportChatController::class, 'pinMessage'])
        ->middleware('permission:support.admin|crm.dashboard.view')
        ->name('chat.pin');
    Route::post('/chat/{conversation}/status', [SupportChatController::class, 'updateConversationStatus'])
        ->middleware('permission:support.admin|crm.dashboard.view')
        ->name('chat.status');
    Route::get('/chat/agents/list', [SupportChatController::class, 'supportAgents'])
        ->middleware('permission:support.admin|crm.dashboard.view')
        ->name('chat.agents');
    Route::post('/chat/{conversation}/assign', [SupportChatController::class, 'assignConversation'])
        ->middleware('permission:support.admin|crm.dashboard.view')
        ->name('chat.assign');

    Route::get('/chat/{conversation}/stream', [SupportChatController::class, 'stream'])
        ->middleware('permission:support.tickets.create|support.tickets.view|support.admin')
        ->name('chat.stream');

    // Chat - admin
    Route::get('/admin/chat', [SupportChatController::class, 'adminIndex'])
        ->middleware('permission:support.admin|crm.dashboard.view')
        ->name('chat.admin');

    // Tickets - utilisateur
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])
        ->middleware('permission:support.tickets.create')
        ->name('tickets.create');

    Route::post('/tickets', [SupportTicketController::class, 'store'])
        ->middleware('permission:support.tickets.create')
        ->name('tickets.store');

    Route::get('/tickets', [SupportTicketController::class, 'myTickets'])
        ->middleware('permission:support.tickets.view')
        ->name('tickets.mine');

    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])
        ->middleware('permission:support.tickets.view')
        ->name('tickets.show');

    Route::get('/tickets/{ticket}/replies', [SupportTicketController::class, 'repliesJson'])
        ->middleware('permission:support.tickets.view')
        ->name('tickets.replies');

    Route::post('/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])
        ->middleware('permission:support.tickets.view')
        ->name('tickets.reply');

    // Tickets - admin / support
    Route::get('/admin/tickets', [SupportTicketController::class, 'index'])
        ->middleware('permission:support.admin')
        ->name('tickets.index');

    Route::post('/tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])
        ->middleware('permission:support.admin')
        ->name('tickets.assign');

    Route::post('/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])
        ->middleware('permission:support.admin')
        ->name('tickets.status');

    // Incidents
    Route::get('/incidents', [SupportTicketController::class, 'incidents'])
        ->middleware('permission:support.admin')
        ->name('incidents.index');

    // FAQ / Base de connaissance
    Route::get('/faq', [SupportFaqController::class, 'index'])
        ->name('faq.index');

    // Contact support
    Route::get('/contact', [SupportContactController::class, 'showForm'])
        ->name('contact.show');

    Route::post('/contact', [SupportContactController::class, 'send'])
        ->name('contact.send');

    // Statut système
    Route::get('/status', [SupportStatusController::class, 'index'])
        ->middleware('permission:support.admin')
        ->name('status.index');
});

// Public chat (landing) - no auth
Route::prefix('support')->name('support.')->group(function () {
    Route::post('/public-chat/conversation', [SupportChatController::class, 'ensureGuestConversation'])
        ->name('public-chat.conversation');
    Route::post('/public-chat/heartbeat', [SupportChatController::class, 'guestHeartbeat'])
        ->name('public-chat.heartbeat');
    Route::get('/public-chat/support-online', [SupportChatController::class, 'supportOnline'])
        ->name('public-chat.support-online');
    Route::get('/public-chat/{conversation}/messages', [SupportChatController::class, 'messages'])
        ->name('public-chat.messages');
    Route::post('/public-chat/{conversation}/messages', [SupportChatController::class, 'sendGuest'])
        ->name('public-chat.send');
    Route::get('/public-chat/{conversation}/stream', [SupportChatController::class, 'stream'])
        ->name('public-chat.stream');
});

