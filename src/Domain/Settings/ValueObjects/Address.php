<?php

namespace Src\Domain\Settings\ValueObjects;

/**
 * Value Object: Address
 * 
 * Encapsule une adresse complète
 */
class Address
{
    public function __construct(
        private ?string $street = null,
        private ?string $city = null,
        private ?string $postalCode = null,
        private ?string $country = null
    ) {}

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function updateStreet(?string $street): self
    {
        return new self($street, $this->city, $this->postalCode, $this->country);
    }

    public function updateCity(?string $city): self
    {
        return new self($this->street, $city, $this->postalCode, $this->country);
    }

    public function updatePostalCode(?string $postalCode): self
    {
        return new self($this->street, $this->city, $postalCode, $this->country);
    }

    public function updateCountry(?string $country): self
    {
        return new self($this->street, $this->city, $this->postalCode, $country);
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }
}
