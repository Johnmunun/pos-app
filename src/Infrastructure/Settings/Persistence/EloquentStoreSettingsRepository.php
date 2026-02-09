<?php

namespace Src\Infrastructure\Settings\Persistence;

use Src\Domain\Settings\Repositories\StoreSettingsRepositoryInterface;
use Src\Domain\Settings\Entities\StoreSettings;
use Src\Domain\Settings\ValueObjects\CompanyIdentity;
use Src\Domain\Settings\ValueObjects\Address;
use Src\Infrastructure\Settings\Models\StoreSettingsModel;

/**
 * Repository: EloquentStoreSettingsRepository
 * 
 * Implémentation Eloquent du repository StoreSettings
 */
class EloquentStoreSettingsRepository implements StoreSettingsRepositoryInterface
{
    public function findByShopId(string $shopId): ?StoreSettings
    {
        $model = StoreSettingsModel::where('shop_id', $shopId)->first();
        
        if (!$model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function save(StoreSettings $settings): void
    {
        $shopId = $settings->getShopId();
        
        \Log::info('EloquentStoreSettingsRepository::save - Starting', [
            'shop_id' => $shopId,
            'settings_id' => $settings->getId(),
        ]);
        
        $model = StoreSettingsModel::where('shop_id', $shopId)->first();

        if (!$model) {
            $model = new StoreSettingsModel();
            // Pour une nouvelle entrée, utiliser l'UUID généré par l'entité
            $model->id = $settings->getId();
            // S'assurer que shop_id est un entier (la table attend unsignedBigInteger)
            $model->shop_id = (int) $shopId;
            \Log::info('Creating new StoreSettingsModel', [
                'id' => $model->id,
                'shop_id' => $model->shop_id,
                'shop_id_type' => gettype($model->shop_id),
            ]);
        } else {
            \Log::info('Updating existing StoreSettingsModel', [
                'id' => $model->id,
                'shop_id' => $shopId,
            ]);
        }

        $companyIdentity = $settings->getCompanyIdentity();
        $address = $settings->getAddress();

        // Assigner toutes les valeurs avec logs
        $model->company_name = $companyIdentity->getName();
        $model->id_nat = $companyIdentity->getIdNat();
        $model->rccm = $companyIdentity->getRccm();
        $model->tax_number = $companyIdentity->getTaxNumber();
        
        $model->street = $address->getStreet();
        $model->city = $address->getCity();
        $model->postal_code = $address->getPostalCode();
        $model->country = $address->getCountry();
        
        $model->phone = $settings->getPhone();
        $model->email = $settings->getEmail();
        $model->logo_path = $settings->getLogoPath();
        $model->currency = $settings->getCurrency();
        $model->exchange_rate = $settings->getExchangeRate();
        $model->invoice_footer_text = $settings->getInvoiceFooterText();

        \Log::info('StoreSettingsModel data before save', [
            'shop_id' => $model->shop_id,
            'company_name' => $model->company_name,
            'currency' => $model->currency,
            'id' => $model->id,
            'is_dirty' => $model->isDirty(),
            'dirty_attributes' => $model->getDirty(),
        ]);

        // Sauvegarder et vérifier les erreurs
        try {
            $saved = $model->save();
            
            \Log::info('Model save result', [
                'saved' => $saved,
                'model_exists' => $model->exists,
                'model_id' => $model->id,
            ]);
            
            if (!$saved) {
                \Log::error('Failed to save StoreSettings - save() returned false', [
                    'shop_id' => $shopId,
                    'model_data' => $model->getAttributes(),
                    'model_errors' => method_exists($model, 'getErrors') ? $model->getErrors() : 'N/A',
                ]);
                throw new \RuntimeException('Failed to save store settings - save() returned false');
            }
            
            // Vérifier que l'enregistrement existe bien en base
            $verify = StoreSettingsModel::find($model->id);
            if (!$verify) {
                \Log::error('StoreSettings not found after save', [
                    'shop_id' => $shopId,
                    'settings_id' => $model->id,
                ]);
                throw new \RuntimeException('StoreSettings not found in database after save');
            }
            
            // Mettre à jour l'ID de l'entité si c'était une création
            if ($settings->getId() !== $model->id) {
                $reflection = new \ReflectionClass($settings);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($settings, $model->id);
            }
            
            \Log::info('StoreSettings saved successfully', [
                'shop_id' => $shopId,
                'settings_id' => $model->id,
                'company_name' => $model->company_name,
                'currency' => $model->currency,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database QueryException saving StoreSettings', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? [],
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Exception saving StoreSettings', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function existsForShop(string $shopId): bool
    {
        return StoreSettingsModel::where('shop_id', $shopId)->exists();
    }

    public function deleteByShopId(string $shopId): void
    {
        StoreSettingsModel::where('shop_id', $shopId)->delete();
    }

    /**
     * Convertit un modèle Eloquent en entité Domain
     */
    private function toDomainEntity(StoreSettingsModel $model): StoreSettings
    {
        $companyIdentity = new CompanyIdentity(
            $model->company_name,
            $model->id_nat,
            $model->rccm,
            $model->tax_number
        );

        $address = new Address(
            $model->street,
            $model->city,
            $model->postal_code,
            $model->country
        );

        $settings = new StoreSettings(
            $model->id,
            (string) $model->shop_id,
            $companyIdentity,
            $address,
            $model->phone,
            $model->email,
            $model->logo_path,
            $model->currency ?? 'XAF',
            $model->exchange_rate ? (float) $model->exchange_rate : null,
            $model->invoice_footer_text
        );

        // Utiliser les timestamps du modèle
        if ($model->created_at) {
            $reflection = new \ReflectionClass($settings);
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setAccessible(true);
            $createdAtProp->setValue($settings, \DateTimeImmutable::createFromMutable($model->created_at));
        }

        if ($model->updated_at) {
            $reflection = new \ReflectionClass($settings);
            $updatedAtProp = $reflection->getProperty('updatedAt');
            $updatedAtProp->setAccessible(true);
            $updatedAtProp->setValue($settings, \DateTimeImmutable::createFromMutable($model->updated_at));
        }

        return $settings;
    }
}
