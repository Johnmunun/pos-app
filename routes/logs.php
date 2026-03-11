<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Logs\Http\Controllers\SystemLogController;
use Src\Infrastructure\Logs\Http\Controllers\UserActivityController;
use Src\Infrastructure\Logs\Http\Controllers\UserLoginHistoryController;

Route::middleware(['auth', 'verified'])->prefix('logs')->name('logs.')->group(function () {
    Route::get('/system', [SystemLogController::class, 'index'])
        ->middleware('permission:logs.system')
        ->name('system');

    Route::get('/system/download', [SystemLogController::class, 'download'])
        ->middleware('permission:logs.system')
        ->name('system.download');

    Route::get('/actions', [UserActivityController::class, 'index'])
        ->middleware('permission:logs.actions')
        ->name('actions');

    Route::get('/connections', [UserLoginHistoryController::class, 'index'])
        ->middleware('permission:logs.connections')
        ->name('connections');
});

