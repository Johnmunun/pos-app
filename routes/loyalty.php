<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Loyalty\Http\Controllers\LoyaltyController;
use Src\Infrastructure\Loyalty\Http\Controllers\LoyaltyReportController;
use Src\Infrastructure\Loyalty\Http\Controllers\LoyaltySettingsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/loyalty/settings', [LoyaltySettingsController::class, 'index'])
        ->name('loyalty.settings.index');
    Route::put('/loyalty/settings', [LoyaltySettingsController::class, 'update'])
        ->name('loyalty.settings.update');

    Route::get('/loyalty/reports', [LoyaltyReportController::class, 'index'])
        ->name('loyalty.reports.index');

    Route::get('/api/loyalty/lookup', [LoyaltyController::class, 'lookup'])
        ->name('loyalty.lookup');
    Route::get('/api/loyalty/account', [LoyaltyController::class, 'account'])
        ->name('loyalty.account');
    Route::get('/api/loyalty/preview', [LoyaltyController::class, 'preview'])
        ->name('loyalty.preview');
    Route::get('/api/loyalty/accounts/{accountId}/history', [LoyaltyController::class, 'history'])
        ->name('loyalty.history');
});
