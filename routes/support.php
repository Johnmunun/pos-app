<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Support\Http\Controllers\SupportTicketController;
use Src\Infrastructure\Support\Http\Controllers\SupportFaqController;
use Src\Infrastructure\Support\Http\Controllers\SupportContactController;
use Src\Infrastructure\Support\Http\Controllers\SupportStatusController;

Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
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

