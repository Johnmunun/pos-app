<?php

namespace Src\Domains\Onboarding\Services;

use Src\Domains\Onboarding\Entities\OnboardingSession;
use Src\Domains\Onboarding\ValueObjects\Sector;
use Src\Domains\Onboarding\ValueObjects\BusinessType;
use Domains\User\Entities\User;
use Src\Domains\Tenant\Entities\Tenant;

/**
 * Service d'onboarding - Logique domaine
 * 
 * Orchestre le processus d'onboarding sans dépendre de l'infrastructure
 */
class OnboardingService
{
    public function processStep1(OnboardingSession $session, array $data): void
    {
        // Validation business
        $this->validateStep1Data($data);
        
        $session->setStepData(1, [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] // Hashé par l'infrastructure
        ]);
    }

    public function processStep2(OnboardingSession $session, array $data): void
    {
        // Validation business
        $this->validateStep2Data($data);
        
        $sector = new Sector($data['sector']);
        $businessType = new BusinessType($data['business_type']);
        
        $session->setStepData(2, [
            'sector' => $sector->getValue(),
            'business_type' => $businessType->getValue(),
            'sector_label' => $sector->getLabel(),
            'business_type_label' => $businessType->getLabel()
        ]);
    }

    public function processStep3(OnboardingSession $session, array $data): void
    {
        // Validation business
        $this->validateStep3Data($data);
        
        $session->setStepData(3, [
            'company_name' => $data['company_name'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null
        ]);
    }

    public function processStep4(OnboardingSession $session, array $data): void
    {
        // Cette étape est optionnelle
        $legalData = [
            'idnat' => $data['idnat'] ?? null,
            'rccm' => $data['rccm'] ?? null,
            'tax_id' => $data['tax_id'] ?? null
        ];
        
        $session->setStepData(4, array_filter($legalData));
    }

    public function completeRegistration(OnboardingSession $session): array
    {
        if ($session->isComplete()) {
            throw new \DomainException("Cette session d'onboarding est déjà terminée");
        }

        $allData = $session->getAllData();
        
        // Validation complète
        $this->validateAllSteps($allData);
        
        // Marquer comme complète
        $session->markAsComplete();
        
        // Retourner les données prêtes pour l'infrastructure
        return $this->prepareUserData($allData);
    }

    private function validateStep1Data(array $data): void
    {
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException("Tous les champs sont requis pour l'étape 1");
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email invalide");
        }
        
        if (strlen($data['password']) < 8) {
            throw new \InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractères");
        }
    }

    private function validateStep2Data(array $data): void
    {
        if (empty($data['sector']) || empty($data['business_type'])) {
            throw new \InvalidArgumentException("Secteur et type de commerce requis");
        }
        
        // Valider que les valeurs existent
        try {
            new Sector($data['sector']);
            new BusinessType($data['business_type']);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Valeurs invalides: " . $e->getMessage());
        }
    }

    private function validateStep3Data(array $data): void
    {
        if (empty($data['company_name']) || empty($data['address']) || empty($data['phone'])) {
            throw new \InvalidArgumentException("Informations de la boutique incomplètes");
        }
    }

    private function validateAllSteps(array $data): void
    {
        // Vérifier que toutes les étapes obligatoires sont présentes
        for ($i = 1; $i <= 3; $i++) {
            if (!isset($data[$i]) || empty($data[$i])) {
                throw new \DomainException("L'étape $i n'est pas complétée");
            }
        }
    }

    private function prepareUserData(array $data): array
    {
        return [
            'user' => [
                'name' => $data[1]['name'],
                'email' => $data[1]['email'],
                'password' => $data[1]['password'], // Sera hashé par l'infrastructure
                'type' => 'MERCHANT',
                'status' => 'pending',
                'is_active' => false
            ],
            'tenant' => [
                'name' => $data[3]['company_name'],
                'sector' => $data[2]['sector'],
                'address' => $data[3]['address'],
                'phone' => $data[3]['phone'],
                'email' => $data[3]['email'] ?? $data[1]['email'],
                'business_type' => $data[2]['business_type'],
                'idnat' => $data[4]['idnat'] ?? null,
                'rccm' => $data[4]['rccm'] ?? null
            ]
        ];
    }
}