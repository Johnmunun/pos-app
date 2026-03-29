<?php

namespace Src\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeRegistrationMail;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant as TenantModel;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\DynamicMailSettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domains\Onboarding\Services\OnboardingService;
use Src\Infrastructure\Referral\Models\ReferralAccountModel;
use Src\Infrastructure\Repositories\EloquentOnboardingRepository;
use Src\Domains\Onboarding\ValueObjects\Sector;
use Src\Domains\Onboarding\ValueObjects\BusinessType;
use Src\Domains\StoreProvisioning\Contracts\StoreTemplateProvisionerInterface;
use Src\Domains\StoreProvisioning\StoreStartMode;

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

    public function __construct(
        private readonly StoreTemplateProvisionerInterface $storeTemplateProvisioner,
    ) {
        $this->onboardingService = new OnboardingService();
        $this->repository = new EloquentOnboardingRepository();
    }

    /**
     * Afficher l'étape 1 : Informations du compte
     */
    public function showStep1(Request $request): Response|RedirectResponse
    {
        // Vérifier si l'utilisateur est déjà connecté
        if (Auth::check()) {
            if (Auth::user()->status === 'pending') {
                return redirect()->route('pending');
            }
            return redirect()->route('dashboard');
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
            'sector' => 'required|in:'.implode(',', array_keys(Sector::getAll())),
            'business_type' => 'required|in:'.implode(',', array_keys(BusinessType::getAll())),
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
            'email' => 'nullable|email|max:255',
            'referral_code' => 'nullable|string|max:100',
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

            return redirect()->route('onboarding.step5');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Dernière étape : choix boutique vide ou préconfigurée (avant création du compte).
     */
    public function showStep5(Request $request): Response
    {
        $this->ensureValidStep($request, 5);
        $session = $this->getOrCreateSession($request);

        $step1Data = $session->getStepData(1);
        $step2Data = $session->getStepData(2);
        $step3Data = $session->getStepData(3);
        $step4Data = $session->getStepData(4);

        return Inertia::render('Onboarding/Step5', [
            'currentStep' => 5,
            'sessionData' => array_merge($step1Data, $step2Data, $step3Data, $step4Data, $session->getStepData(5)),
        ]);
    }

    public function processStep5(Request $request)
    {
        $session = $this->getOrCreateSession($request);

        $startMode = $request->input('start_mode');
        if ($startMode === null || $startMode === '') {
            $request->merge(['start_mode' => StoreStartMode::EMPTY_STORE]);
        }

        $validated = $request->validate([
            'start_mode' => 'required|in:'.implode(',', StoreStartMode::values()),
        ]);

        try {
            $this->onboardingService->mergeStoreStartMode($session, $validated['start_mode']);
            $this->repository->save($session);

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
            return redirect()->route('billing.onboarding.payment');
        }

        try {
            // Obtenir les données prêtes pour la création
            $registrationData = $this->onboardingService->completeRegistration($session);

            $user = null;
            $tenant = null;

            DB::transaction(function () use ($registrationData, &$user, &$tenant) {
                $user = $this->createUser($registrationData['user']);
                $tenant = $this->createTenant($registrationData['tenant']);

                $user->tenant_id = $tenant->id;
                $user->save();

                $this->storeTemplateProvisioner->provisionTenantStore($tenant, $user);
            });

            // Secteurs Kiosque, Supermarché, Boucherie, Autre = module Global Commerce : assigner le rôle Commerçant Commerce
            $commerceSectors = ['kiosk', 'supermarket', 'butchery', 'other', 'global_commerce'];
            if (in_array($tenant->sector, $commerceSectors, true)) {
                $commerceRole = Role::where('name', 'Commerçant Commerce')->whereNull('tenant_id')->first();
                if ($commerceRole) {
                    $user->roles()->syncWithoutDetaching([$commerceRole->id]);
                }
            }

            // Secteur E-commerce = module E-commerce : assigner le rôle Commerçant E-commerce
            if ($tenant->sector === 'ecommerce') {
                $ecommerceRole = Role::where('name', 'Commerçant E-commerce')->whereNull('tenant_id')->first();
                if ($ecommerceRole) {
                    $user->roles()->syncWithoutDetaching([$ecommerceRole->id]);
                } else {
                    // Si le rôle n'existe pas, créer un rôle par défaut avec les permissions e-commerce
                    $ecommerceRole = Role::create([
                        'tenant_id' => null,
                        'name' => 'Commerçant E-commerce',
                        'description' => 'Rôle par défaut pour les commerçants e-commerce (créé automatiquement).',
                        'is_active' => true,
                    ]);
                    
                    // Assigner les permissions e-commerce
                    $ecommercePermissions = Permission::where('is_old', false)
                        ->whereIn('code', [
                            'module.ecommerce',
                            'ecommerce.view',
                            'ecommerce.create',
                            'ecommerce.update',
                            'ecommerce.manage_orders',
                        ])
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($ecommercePermissions)) {
                        $ecommerceRole->permissions()->sync($ecommercePermissions);
                    }
                    
                    $user->roles()->syncWithoutDetaching([$ecommerceRole->id]);
                }
            }

            // Créer le compte de parrainage si un code d'invitation a été fourni
            $step3Data = $session->getStepData(3);
            $rawReferralCode = $step3Data['referral_code'] ?? $request->query('ref');
            if ($rawReferralCode) {
                $referrerAccount = ReferralAccountModel::where('code', $rawReferralCode)->first();
                if ($referrerAccount) {
                    ReferralAccountModel::create([
                        'tenant_id' => (int) $tenant->id,
                        'user_id' => (int) $user->id,
                        'parent_id' => $referrerAccount->id,
                        'code' => strtoupper(Str::random(8)),
                        'currency' => null,
                    ]);
                }
            }

            // Sauvegarder la session comme complète
            $this->repository->save($session);
            
            // Connecter l'utilisateur
            Auth::login($user);

            // Créer une notification système pour ROOT / admins (nouvelle inscription)
            try {
                event(new \App\Events\UserRegisteredNotification($user));
            } catch (\Throwable $e) {
                Log::warning('UserRegisteredNotification dispatch failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $mailService = app(DynamicMailSettingsService::class);
                if ($mailService->applyFromStorage()) {
                    Mail::to($user->email)->send(new WelcomeRegistrationMail(
                        $user,
                        $tenant->name,
                        $registrationData['tenant']['store_start_mode'] ?? null,
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('Welcome email failed after onboarding', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Ajouter un message de succès
            request()->session()->flash('success', 'Votre compte a été créé avec succès ! Il est en attente de validation par notre équipe.');
            
            return redirect()->route('pending');
            
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Onboarding complete failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Impossible de finaliser l’inscription. Réessayez dans quelques instants.']);
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

        $user = Auth::user();
        if ($user && $user->tenant_id && Schema::hasTable('tenant_plan_subscriptions')) {
            $activeSubscription = DB::table('tenant_plan_subscriptions')
                ->where('tenant_id', (string) $user->tenant_id)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first(['id']);

            if (!$activeSubscription) {
                return redirect()->route('billing.onboarding.payment');
            }
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
            redirect()->route('onboarding.step'.($session->getCurrentStep() + 1))->throwResponse();
        }
    }

    /**
     * Créer l'utilisateur (infrastructure)
     */
    private function createUser(array $userData)
    {
        $payload = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'status' => $userData['status'],
        ];

        // Some local DBs are missing onboarding-era columns.
        // Build the payload dynamically to avoid SQL errors.
        if (Schema::hasColumn('users', 'type')) {
            $payload['type'] = $userData['type'];
        }
        if (Schema::hasColumn('users', 'is_active')) {
            $payload['is_active'] = $userData['is_active'];
        }

        return UserModel::create($payload);
    }

    /**
     * Créer le tenant (infrastructure)
     */
    private function createTenant(array $tenantData)
    {
        return TenantModel::create([
            'name' => $tenantData['name'],
            'sector' => $tenantData['sector'],
            'address' => $tenantData['address'],
            'phone' => $tenantData['phone'],
            'email' => $tenantData['email'],
            'business_type' => $tenantData['business_type'],
            'idnat' => $tenantData['idnat'],
            'rccm' => $tenantData['rccm'],
            'is_active' => true,
            'code' => strtoupper(Str::random(8)),
            'store_start_mode' => $tenantData['store_start_mode'] ?? StoreStartMode::EMPTY_STORE,
        ]);
    }
}