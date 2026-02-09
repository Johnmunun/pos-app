<?php

use Illuminate\Support\Facades\Route;
use Src\Infrastructure\Http\Controllers\OnboardingController;

/**
 * Routes d'onboarding - Intégration Breeze
 * 
 * Ces routes coexistent avec Breeze sans conflit
 * La route /register redirige vers l'onboarding
 */

// Redirection de l'ancienne route register
Route::get('/register', function () {
    return redirect()->route('onboarding.step1');
})->name('register')->middleware('guest');

// Routes d'onboarding
Route::middleware(['guest'])->group(function () {
    Route::get('/onboarding/step1', [OnboardingController::class, 'showStep1'])
        ->name('onboarding.step1');
        
    Route::post('/onboarding/step1', [OnboardingController::class, 'processStep1'])
        ->name('onboarding.step1.process');
        
    Route::get('/onboarding/step2', [OnboardingController::class, 'showStep2'])
        ->name('onboarding.step2');
        
    Route::post('/onboarding/step2', [OnboardingController::class, 'processStep2'])
        ->name('onboarding.step2.process');
        
    Route::get('/onboarding/step3', [OnboardingController::class, 'showStep3'])
        ->name('onboarding.step3');
        
    Route::post('/onboarding/step3', [OnboardingController::class, 'processStep3'])
        ->name('onboarding.step3.process');
        
    Route::get('/onboarding/step4', [OnboardingController::class, 'showStep4'])
        ->name('onboarding.step4');
        
    Route::post('/onboarding/step4', [OnboardingController::class, 'processStep4'])
        ->name('onboarding.step4.process');
        
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])
        ->name('onboarding.complete');
});

// Route pour utilisateurs pending
Route::get('/pending', [OnboardingController::class, 'showPending'])
    ->name('pending')
    ->middleware(['auth']);

// Protection du dashboard
Route::middleware(['auth', 'ensure.user.is.active'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia\Inertia::render('Dashboard');
    })->name('dashboard');
});