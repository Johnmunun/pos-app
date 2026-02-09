<?php

namespace Src\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domains\Onboarding\Services\OnboardingService;
use Src\Infrastructure\Repositories\EloquentOnboardingRepository;
use Src\Domains\Onboarding\ValueObjects\Sector;
use Src\Domains\Onboarding\ValueObjects\BusinessType;

/**
 * OnboardingController - Infrastructure Layer
 * 
 * Respecte Breeze tout en implémentant l'onboarding DDD
 * Ne modifie pas les routes Breeze existantes
 */
class OnboardingController extends Controller
{
    private OnboardingService $onboardingService;
    private EloquentOnboardingRepository $repository;

    public function __construct()
    {
        $this->onboardingService = new OnboardingService();
        $this->repository = new EloquentOnboardingRepository();
    }

    /**
     * Afficher l'étape 1 : Informations du compte
     */
    public function showStep1(Request $request): Response
    {
        // Vérifier si l'utilisateur est déjà connecté
        if (Auth::check()) {
            if (Auth::user()->status === 'pending') {
                return Inertia::location(route('pending'));
            }
            return Inertia::location(route('dashboard'));
        }

        // Créer ou récupérer la session
        $session = $this->getOrCreateSession($request);
        
        return Inertia::render('Onboarding/Step1', [
            'currentStep' => 1,
            'sessionData' => $session->getStepData(1)
        ]);
    }

    /**
     * Traiter l'étape 1
     */
    public function processStep1(Request $request)
    {
        $session = $this->getOrCreateSession($request);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $this->onboardingService->processStep1($session, $validated);
            $this->repository->save($session);
            
            return redirect()->route('onboarding.step2');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Afficher l'étape 2 : Secteur et type de commerce
     */
    public function showStep2(Request $request): Response
    {
        $this->ensureValidStep($request, 2);
        $session = $this->getOrCreateSession($request);

        // Récupérer les données de l'étape 1 pour les pré-remplir
        $step1Data = $session->getStepData(1);
        
        return Inertia::render('Onboarding/Step2', [
            'currentStep' => 2,
            'sectors' => Sector::getAll(),
            'businessTypes' => BusinessType::getAll(),
            'sessionData' => array_merge($step1Data, $session->getStepData(2))
        ]);
    }

    /**
     * Traiter l'étape 2
     */
    public function processStep2(Request $request)
    {
        $session = $this->getOrCreateSession($request);
        
        $validated = $request->validate([
            'sector' => 'required|in:' . implode(',', array_keys(Sector::getAll())),
            'business_type' => 'required|in:' . implode(',', array_keys(BusinessType::getAll()))
        ]);

        try {
            $this->onboardingService->processStep2($session, $validated);
            $this->repository->save($session);
            
            return redirect()->route('onboarding.step3');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Afficher l'étape 3 : Informations de la boutique
     */
    public function showStep3(Request $request): Response
    {
        $this->ensureValidStep($request, 3);
        $session = $this->getOrCreateSession($request);

        // Récupérer les données des étapes précédentes
        $step1Data = $session->getStepData(1);
        $step2Data = $session->getStepData(2);
        
        return Inertia::render('Onboarding/Step3', [
            'currentStep' => 3,
            'sessionData' => array_merge($step1Data, $step2Data, $session->getStepData(3))
        ]);
    }

    /**
     * Traiter l'étape 3
     */
    public function processStep3(Request $request)
    {
        $session = $this->getOrCreateSession($request);
        
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255'
        ]);

        try {
            $this->onboardingService->processStep3($session, $validated);
            $this->repository->save($session);
            
            return redirect()->route('onboarding.step4');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Afficher l'étape 4 : Documents légaux (optionnel)
     */
    public function showStep4(Request $request): Response
    {
        $this->ensureValidStep($request, 4);
        $session = $this->getOrCreateSession($request);

        // Récupérer les données des étapes précédentes
        $step1Data = $session->getStepData(1);
        $step2Data = $session->getStepData(2);
        $step3Data = $session->getStepData(3);
        
        return Inertia::render('Onboarding/Step4', [
            'currentStep' => 4,
            'sessionData' => array_merge($step1Data, $step2Data, $step3Data, $session->getStepData(4))
        ]);
    }

    /**
     * Traiter l'étape 4
     */
    public function processStep4(Request $request)
    {
        $session = $this->getOrCreateSession($request);
        
        $validated = $request->validate([
            'idnat' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50'
        ]);

        try {
            $this->onboardingService->processStep4($session, $validated);
            $this->repository->save($session);
            
            // Appeler directement la méthode complete au lieu de rediriger
            return $this->complete($request);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Finaliser l'inscription
     */
    public function complete(Request $request)
    {
        $session = $this->getOrCreateSession($request);
        
        if ($session->isComplete()) {
            return redirect()->route('pending');
        }

        try {
            // Obtenir les données prêtes pour la création
            $registrationData = $this->onboardingService->completeRegistration($session);
            
            // Créer l'utilisateur et le tenant (infrastructure)
            $user = $this->createUser($registrationData['user']);
            $tenant = $this->createTenant($registrationData['tenant']);
            
            // Associer le tenant à l'utilisateur
            $user->tenant_id = $tenant->id;
            $user->save();
            
            // Sauvegarder la session comme complète
            $this->repository->save($session);
            
            // Connecter l'utilisateur
            Auth::login($user);
            
            // Ajouter un message de succès
            request()->session()->flash('success', 'Votre compte a été créé avec succès ! Il est en attente de validation par notre équipe.');
            
            return redirect()->route('pending');
            
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Page "Compte en attente"
     */
    public function showPending(): Response|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->status === 'active') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Account/Pending', [
            'auth' => [
                'user' => Auth::user()
            ]
        ]);
    }

    /**
     * Obtenir ou créer une session d'onboarding
     */
    private function getOrCreateSession(Request $request): \Src\Domains\Onboarding\Entities\OnboardingSession
    {
        $sessionId = $request->session()->get('onboarding_session_id');
        
        if ($sessionId) {
            $session = $this->repository->findById($sessionId);
            if ($session && !$session->isComplete()) {
                return $session;
            }
        }

        // Créer une nouvelle session
        $session = $this->repository->createSession();
        $request->session()->put('onboarding_session_id', $session->getId());
        
        return $session;
    }

    /**
     * S'assurer que l'utilisateur peut accéder à l'étape demandée
     */
    private function ensureValidStep(Request $request, int $requiredStep): void
    {
        if (Auth::check() && Auth::user()->status !== 'pending') {
            abort(403);
        }

        $session = $this->getOrCreateSession($request);
        
        if ($session->getCurrentStep() < ($requiredStep - 1)) {
            redirect()->route('onboarding.step' . $session->getCurrentStep() + 1)->throwResponse();
        }
    }

    /**
     * Créer l'utilisateur (infrastructure)
     */
    private function createUser(array $userData)
    {
        return \App\Models\User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($userData['password']),
            'type' => $userData['type'],
            'status' => $userData['status'],
            'is_active' => $userData['is_active']
        ]);
    }

    /**
     * Créer le tenant (infrastructure)
     */
    private function createTenant(array $tenantData)
    {
        return \App\Models\Tenant::create([
            'name' => $tenantData['name'],
            'sector' => $tenantData['sector'],
            'address' => $tenantData['address'],
            'phone' => $tenantData['phone'],
            'email' => $tenantData['email'],
            'business_type' => $tenantData['business_type'],
            'idnat' => $tenantData['idnat'],
            'rccm' => $tenantData['rccm'],
            'is_active' => true,
            'code' => strtoupper(\Illuminate\Support\Str::random(8))
        ]);
    }
}