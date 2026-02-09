<?php

namespace Src\Domain\Settings\ValueObjects;

/**
 * Value Object: CompanyIdentity
 * 
 * Encapsule l'identité légale d'une entreprise
 */
class CompanyIdentity
{
    public function __construct(
        private string $name,
        private ?string $idNat = null,
        private ?string $rccm = null,
        private ?string $taxNumber = null
    ) {
        $this->validate();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdNat(): ?string
    {
        return $this->idNat;
    }

    public function getRccm(): ?string
    {
        return $this->rccm;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function updateName(string $name): self
    {
        return new self($name, $this->idNat, $this->rccm, $this->taxNumber);
    }

    public function updateIdNat(?string $idNat): self
    {
        return new self($this->name, $idNat, $this->rccm, $this->taxNumber);
    }

    public function updateRccm(?string $rccm): self
    {
        return new self($this->name, $this->idNat, $rccm, $this->taxNumber);
    }

    public function updateTaxNumber(?string $taxNumber): self
    {
        return new self($this->name, $this->idNat, $this->rccm, $taxNumber);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'id_nat' => $this->idNat,
            'rccm' => $this->rccm,
            'tax_number' => $this->taxNumber,
        ];
    }

    private function validate(): void
    {
        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Le nom de l\'entreprise est obligatoire');
        }
    }
}
