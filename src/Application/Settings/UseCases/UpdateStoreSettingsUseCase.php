<?php

namespace Src\Application\Settings\UseCases;

use Src\Domain\Settings\Repositories\StoreSettingsRepositoryInterface;
use Src\Domain\Settings\Entities\StoreSettings;
use Src\Domain\Settings\ValueObjects\CompanyIdentity;
use Src\Domain\Settings\ValueObjects\Address;
use Src\Application\Settings\DTO\UpdateStoreSettingsDTO;

/**
 * Use Case: UpdateStoreSettings
 * 
 * Met à jour ou crée les paramètres d'une boutique
 * Sécurité: Le shopId doit venir de l'utilisateur connecté (vérifié dans le Controller)
 */
class UpdateStoreSettingsUseCase
{
    public function __construct(
        private StoreSettingsRepositoryInterface $repository
    ) {}

    public function execute(UpdateStoreSettingsDTO $dto): StoreSettings
    {
        // Vérifier si des paramètres existent déjà
        $existingSettings = $this->repository->findByShopId($dto->shopId);

        // Créer les Value Objects
        $companyIdentity = new CompanyIdentity(
            $dto->companyName,
            $dto->idNat,
            $dto->rccm,
            $dto->taxNumber
        );

        $address = new Address(
            $dto->street,
            $dto->city,
            $dto->postalCode,
            $dto->country
        );

        if ($existingSettings) {
            // Mise à jour
            $existingSettings->updateCompanyIdentity($companyIdentity);
            $existingSettings->updateAddress($address);
            $existingSettings->updatePhone($dto->phone);
            $existingSettings->updateEmail($dto->email);
            
            if ($dto->logoPath !== null) {
                $existingSettings->updateLogo($dto->logoPath);
            }
            
            $existingSettings->updateCurrency($dto->currency);
            $existingSettings->updateExchangeRate($dto->exchangeRate);
            $existingSettings->updateInvoiceFooterText($dto->invoiceFooterText);

            $this->repository->save($existingSettings);
            return $existingSettings;
        } else {
            // Création
            $settings = StoreSettings::create(
                $dto->shopId,
                $companyIdentity,
                $address,
                $dto->phone,
                $dto->email,
                $dto->logoPath,
                $dto->currency,
                $dto->exchangeRate,
                $dto->invoiceFooterText
            );

            $this->repository->save($settings);
            return $settings;
        }
    }
}
