<?php

namespace Src\Infrastructure\Repositories;

use Src\Domains\Onboarding\Entities\OnboardingSession;
use Src\Domains\Onboarding\Repositories\OnboardingRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Repository Eloquent pour OnboardingSession
 * 
 * Implémentation de l'infrastructure utilisant Laravel Cache
 */
class EloquentOnboardingRepository implements OnboardingRepository
{
    private const CACHE_TTL = 3600; // 1 heure
    private const CACHE_PREFIX = 'onboarding_session_';

    public function findById(string $id): ?OnboardingSession
    {
        $data = Cache::get(self::CACHE_PREFIX . $id);
        
        if (!$data) {
            return null;
        }

        $session = new OnboardingSession($id);
        
        // Restaurer l'état de la session
        if (isset($data['step_data'])) {
            $reflection = new \ReflectionClass($session);
            $stepDataProperty = $reflection->getProperty('stepData');
            $stepDataProperty->setAccessible(true);
            $stepDataProperty->setValue($session, $data['step_data']);
        }
        
        if (isset($data['current_step'])) {
            $reflection = new \ReflectionClass($session);
            $currentStepProperty = $reflection->getProperty('currentStep');
            $currentStepProperty->setAccessible(true);
            $currentStepProperty->setValue($session, $data['current_step']);
        }
        
        if (isset($data['completed_at'])) {
            $reflection = new \ReflectionClass($session);
            $completedAtProperty = $reflection->getProperty('completedAt');
            $completedAtProperty->setAccessible(true);
            $completedAtProperty->setValue($session, new \DateTimeImmutable($data['completed_at']));
        }

        return $session;
    }

    public function save(OnboardingSession $session): void
    {
        $data = [
            'step_data' => $session->getAllData(),
            'current_step' => $session->getCurrentStep(),
            'created_at' => $session->getCreatedAt()->format('c'),
            'completed_at' => $session->getCompletedAt()?->format('c')
        ];

        Cache::put(
            self::CACHE_PREFIX . $session->getId(),
            $data,
            self::CACHE_TTL
        );
    }

    public function delete(OnboardingSession $session): void
    {
        Cache::forget(self::CACHE_PREFIX . $session->getId());
    }

    public function cleanupExpired(\DateTimeImmutable $before): int
    {
        // Laravel Cache gère l'expiration automatiquement
        // Cette méthode est plus utile avec une base de données
        return 0;
    }

    public function createSession(): OnboardingSession
    {
        $id = uniqid('os_', true);
        $session = new OnboardingSession($id);
        $this->save($session);
        return $session;
    }
}