<?php

namespace Src\Application\Settings\DTO;

/**
 * DTO: UpdateStoreSettingsDTO
 * 
 * Data Transfer Object pour la mise à jour des paramètres de boutique
 */
class UpdateStoreSettingsDTO
{
    public function __construct(
        public readonly string $shopId,
        public readonly string $companyName,
        public readonly ?string $idNat = null,
        public readonly ?string $rccm = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $country = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $logoPath = null,
        public readonly string $currency = 'XAF',
        public readonly ?float $exchangeRate = null,
        public readonly ?string $invoiceFooterText = null,
        public readonly bool $receiptAutoPrint = false
    ) {}
}
