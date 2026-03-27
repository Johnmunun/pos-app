<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Crm\Http\Controllers\CrmDashboardController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('crm.')
    ->group(function () {
        Route::get('/crm', [CrmDashboardController::class, 'index'])
            ->middleware('permission:crm.dashboard.view')
            ->name('dashboard');

        Route::post('/crm/whatsapp', [CrmDashboardController::class, 'updateWhatsapp'])
            ->middleware('permission:crm.dashboard.view')
            ->name('whatsapp.update');
    });

