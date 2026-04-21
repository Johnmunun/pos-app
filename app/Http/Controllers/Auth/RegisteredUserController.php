<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\PostRegistrationPaymentReminderMail;
use App\Mail\WelcomeRegistrationMail;
use App\Services\DynamicMailSettingsService;
use Src\Infrastructure\Referral\Models\ReferralAccountModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Billing\Services\BillingPlanService;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => 'nullable|string|max:100',
        ]);

        // Générer un slug unique
        $baseSlug = Str::slug($request->company_name);
        $slug = $baseSlug;
        $counter = 1;
        
        // Vérifier l'unicité du slug
        while (DB::table('tenants')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Créer un tenant pour cette entreprise
        $tenant = DB::table('tenants')->insertGetId([
            'code' => strtoupper(Str::random(8)), // Code unique aléatoire
            'name' => $request->company_name,
            'slug' => $slug,
            'email' => $request->email, // Utiliser l'email de l'utilisateur comme email du tenant
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Extraire prénom et nom
        $nameParts = explode(' ', $request->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Créer l'utilisateur avec le tenant_id
        $user = User::create([
            'name' => $request->name, // Nom complet (requis par la table)
            'tenant_id' => $tenant,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'TENANT_ADMIN', // Premier utilisateur = admin du tenant
            'status' => 'active',
            'is_active' => true,
        ]);

        $trialPlanId = (int) (DB::table('billing_plans')->where('code', 'trial')->value('id') ?? 0);
        if ($trialPlanId <= 0) {
            $trialPlanId = (int) (DB::table('billing_plans')->where('is_default', true)->value('id') ?? 0);
        }
        if ($trialPlanId > 0) {
            app(BillingPlanService::class)->assignTenantPlan((string) $tenant, $trialPlanId, 'active');
        }

        // Si un code de parrainage est présent, créer le compte referral avec parent
        $rawReferralCode = $request->input('referral_code') ?: $request->query('ref');
        if ($rawReferralCode) {
            $referrerAccount = ReferralAccountModel::where('code', $rawReferralCode)->first();
            if ($referrerAccount) {
                ReferralAccountModel::create([
                    'tenant_id' => (int) $tenant,
                    'user_id' => (int) $user->id,
                    'parent_id' => $referrerAccount->id,
                    'code' => strtoupper(Str::random(8)),
                    'currency' => null,
                ]);
            }
        }

        event(new Registered($user));

        $this->sendRegistrationEmails($user, (string) $request->company_name);

        Auth::login($user);
        $request->session()->flash('trial_upgrade_prompt', true);

        return redirect(route('dashboard', absolute: false));
    }

    private function sendRegistrationEmails(User $user, string $companyName): void
    {
        /** @var DynamicMailSettingsService $mailService */
        $mailService = app(DynamicMailSettingsService::class);
        $dynamicApplied = $mailService->applyFromStorage();

        $sendAll = function () use ($user, $companyName): void {
            Mail::to($user->email)->send(new WelcomeRegistrationMail($user, $companyName));
            Mail::to($user->email)->send(new PostRegistrationPaymentReminderMail($user, $companyName));
        };

        try {
            $sendAll();
        } catch (Throwable $e) {
            Log::warning('Registration emails failed (primary mailer)', [
                'user_id' => $user->id,
                'dynamic_mail_applied' => $dynamicApplied,
                'error' => $e->getMessage(),
            ]);

            if (!$dynamicApplied) {
                return;
            }

            try {
                $this->restoreEnvMailConfig();
                $sendAll();
            } catch (Throwable $retryError) {
                Log::error('Registration emails failed (env fallback)', [
                    'user_id' => $user->id,
                    'error' => $retryError->getMessage(),
                ]);
            }
        }
    }

    private function restoreEnvMailConfig(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.transport', 'smtp');
        config()->set('mail.mailers.smtp.host', (string) config('mail.mailers.smtp.host', ''));
        config()->set('mail.mailers.smtp.port', (int) config('mail.mailers.smtp.port', 587));
        config()->set('mail.mailers.smtp.encryption', config('mail.mailers.smtp.encryption'));
        config()->set('mail.mailers.smtp.username', (string) config('mail.mailers.smtp.username', ''));
        config()->set('mail.mailers.smtp.password', (string) config('mail.mailers.smtp.password', ''));
        config()->set('mail.from.address', (string) config('mail.from.address', ''));
        config()->set('mail.from.name', (string) config('mail.from.name', 'OmniPOS'));

        try {
            Mail::purge('smtp');
        } catch (Throwable) {
            // noop
        }
    }
}
