<?php

namespace Src\Infrastructure\Settings\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Application\Settings\UseCases\UpdateStoreSettingsUseCase;
use Src\Application\Settings\DTO\UpdateStoreSettingsDTO;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;

/**
 * Controller: SettingsController
 * 
 * Gère les paramètres de boutique
 * SÉCURITÉ: Le shop_id vient TOUJOURS de l'utilisateur connecté, jamais du frontend
 */
class SettingsController
{
    public function __construct(
        private GetStoreSettingsUseCase $getSettingsUseCase,
        private UpdateStoreSettingsUseCase $updateSettingsUseCase,
        private StoreLogoService $logoService
    ) {}

    /**
     * Affiche la page des paramètres
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        
        // SÉCURITÉ: Récupérer le shop_id depuis l'utilisateur connecté
        $shopId = $this->getShopIdFromUser($user);
        
        if (!$shopId && !$user->isRoot()) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        // ROOT peut voir toutes les boutiques, mais pour l'instant on utilise son shop_id s'il existe
        // Si ROOT n'a pas de shop_id, on pourrait lui permettre de sélectionner une boutique
        // Pour l'instant, on exige un shop_id même pour ROOT
        if ($user->isRoot() && !$shopId) {
            // ROOT sans shop_id: on pourrait rediriger vers une page de sélection
            // Pour l'instant, on retourne une erreur
            abort(403, 'ROOT user must have a shop_id to view settings.');
        }

        // Récupérer les paramètres
        $settings = $this->getSettingsUseCase->execute($shopId);

        // Préparer les données pour Inertia
        $settingsData = null;
        if ($settings) {
            $companyIdentity = $settings->getCompanyIdentity();
            $address = $settings->getAddress();
            
            $settingsData = [
                'id' => $settings->getId(),
                'shop_id' => $settings->getShopId(),
                'company_name' => $companyIdentity->getName(),
                'id_nat' => $companyIdentity->getIdNat(),
                'rccm' => $companyIdentity->getRccm(),
                'tax_number' => $companyIdentity->getTaxNumber(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'postal_code' => $address->getPostalCode(),
                'country' => $address->getCountry(),
                'phone' => $settings->getPhone(),
                'email' => $settings->getEmail(),
                'logo_path' => $settings->getLogoPath(),
                'logo_url' => $this->logoService->getUrl($settings->getLogoPath()),
                'currency' => $settings->getCurrency(),
                'exchange_rate' => $settings->getExchangeRate(),
                'invoice_footer_text' => $settings->getInvoiceFooterText(),
                'is_complete' => $settings->isComplete(),
            ];
        }

        // Vérifier les permissions
        $permissions = $user->permissionCodes();
        $canView = $user->isRoot() || in_array('settings.view', $permissions);
        $canUpdate = $user->isRoot() || in_array('settings.update', $permissions);

        return Inertia::render('Settings/Index', [
            'settings' => $settingsData,
            'permissions' => [
                'view' => $canView,
                'update' => $canUpdate,
            ],
        ]);
    }

    /**
     * Met à jour les paramètres
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        
        // SÉCURITÉ: Récupérer le shop_id depuis l'utilisateur connecté (IGNORER le frontend)
        $shopId = $this->getShopIdFromUser($user);
        
        if (!$shopId && !$user->isRoot()) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        if ($user->isRoot() && !$shopId) {
            abort(403, 'ROOT user must have a shop_id to update settings.');
        }

        // Validation
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'id_nat' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'invoice_footer_text' => 'nullable|string|max:1000',
        ]);

        try {
            // Gérer le logo
            $logoPath = null;
            $existingSettings = $this->getSettingsUseCase->execute($shopId);
            
            if ($request->hasFile('logo')) {
                // Upload nouveau logo
                $logoPath = $this->logoService->upload($request->file('logo'), $shopId);
                
                // Supprimer l'ancien logo si existe
                if ($existingSettings && $existingSettings->getLogoPath()) {
                    $this->logoService->deleteByPath($existingSettings->getLogoPath());
                }
            } elseif ($request->boolean('remove_logo')) {
                // Supprimer le logo existant
                if ($existingSettings && $existingSettings->getLogoPath()) {
                    $this->logoService->deleteByPath($existingSettings->getLogoPath());
                }
                $logoPath = null;
            } elseif ($existingSettings) {
                // Garder l'ancien logo
                $logoPath = $existingSettings->getLogoPath();
            }

            // Créer le DTO
            $dto = new UpdateStoreSettingsDTO(
                $shopId,
                $validated['company_name'],
                $validated['id_nat'] ?? null,
                $validated['rccm'] ?? null,
                $validated['tax_number'] ?? null,
                $validated['street'] ?? null,
                $validated['city'] ?? null,
                $validated['postal_code'] ?? null,
                $validated['country'] ?? null,
                $validated['phone'] ?? null,
                $validated['email'] ?? null,
                $logoPath,
                $validated['currency'],
                $validated['exchange_rate'] ?? null,
                $validated['invoice_footer_text'] ?? null
            );

            // Exécuter le Use Case
            Log::info('Executing UpdateStoreSettingsUseCase', [
                'shop_id' => $shopId,
                'company_name' => $validated['company_name'],
                'currency' => $validated['currency'],
            ]);
            
            $savedSettings = $this->updateSettingsUseCase->execute($dto);
            
            Log::info('StoreSettings saved successfully', [
                'shop_id' => $shopId,
                'settings_id' => $savedSettings->getId(),
                'company_name' => $savedSettings->getCompanyIdentity()->getName(),
            ]);

            return redirect()->route('settings.index')
                ->with('success', 'Paramètres mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Error updating store settings', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId,
                'user_id' => $user->id,
            ]);

            return redirect()->back()
                ->withErrors(['message' => 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Récupère le shop_id depuis l'utilisateur connecté
     * SÉCURITÉ: Ne jamais faire confiance au frontend
     * 
     * Si l'utilisateur n'a pas de shop_id, on récupère ou crée le premier shop du tenant
     */
    private function getShopIdFromUser($user): ?string
    {
        // Essayer shop_id d'abord
        if (isset($user->shop_id) && $user->shop_id) {
            Log::info('Using user shop_id', ['shop_id' => $user->shop_id, 'user_id' => $user->id]);
            return (string) $user->shop_id;
        }

        // Si pas de shop_id, utiliser le premier shop du tenant ou en créer un
        if (isset($user->tenant_id) && $user->tenant_id) {
            $shop = \App\Models\Shop::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->first();
            
            if ($shop) {
                Log::info('Using existing shop for tenant', ['shop_id' => $shop->id, 'tenant_id' => $user->tenant_id]);
                return (string) $shop->id;
            }
            
            // Créer un shop par défaut pour ce tenant
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            if ($tenant) {
                try {
                    $shop = \App\Models\Shop::create([
                        'tenant_id' => $user->tenant_id,
                        'name' => $tenant->name . ' - Boutique principale',
                        'code' => $tenant->code . '-SHOP-1',
                        'type' => 'physical',
                        'is_active' => true,
                    ]);
                    
                    Log::info('Created default shop for tenant', [
                        'shop_id' => $shop->id,
                        'tenant_id' => $user->tenant_id,
                        'shop_code' => $shop->code
                    ]);
                    
                    return (string) $shop->id;
                } catch (\Exception $e) {
                    Log::error('Failed to create default shop', [
                        'tenant_id' => $user->tenant_id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }
        }

        Log::warning('No shop_id found for user', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id ?? null]);
        return null;
    }
}
