<?php

namespace Src\Domains\Onboarding\Repositories;

use Src\Domains\Onboarding\Entities\OnboardingSession;

/**
 * Interface OnboardingRepository
 * 
 * Contrat pour la persistance des sessions d'onboarding
 */
interface OnboardingRepository
{
    public function findById(string $id): ?OnboardingSession;
    
    public function save(OnboardingSession $session): void;
    
    public function delete(OnboardingSession $session): void;
    
    public function cleanupExpired(\DateTimeImmutable $before): int;
}