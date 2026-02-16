<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Value Object representing an expiration date for pharmaceutical products.
 */
final class ExpirationDate
{
    private DateTimeImmutable $value;

    public function __construct(DateTimeImmutable $value)
    {
        // Normalize to start of day for consistent comparisons
        $normalized = $value->setTime(0, 0, 0);
        $this->value = $normalized;
    }

    public static function fromString(string $dateString): self
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateString);
        
        if ($date === false) {
            throw new InvalidArgumentException("Format de date invalide: {$dateString}. Format attendu: YYYY-MM-DD");
        }
        
        return new self($date);
    }

    public function getValue(): DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = 'Y-m-d'): string
    {
        return $this->value->format($format);
    }

    /**
     * Check if the product is expired as of the given date.
     */
    public function isExpired(?DateTimeImmutable $asOf = null): bool
    {
        $referenceDate = $asOf ?? new DateTimeImmutable();
        $referenceDate = $referenceDate->setTime(0, 0, 0);
        
        return $this->value < $referenceDate;
    }

    /**
     * Check if the product expires within the given number of days.
     */
    public function expiresWithinDays(int $days, ?DateTimeImmutable $asOf = null): bool
    {
        if ($days < 0) {
            throw new InvalidArgumentException('Le nombre de jours doit être positif.');
        }
        
        $referenceDate = $asOf ?? new DateTimeImmutable();
        $referenceDate = $referenceDate->setTime(0, 0, 0);
        $threshold = $referenceDate->modify("+{$days} days");
        
        return $this->value <= $threshold && !$this->isExpired($referenceDate);
    }

    /**
     * Get the number of days until expiration.
     * Returns negative value if already expired.
     */
    public function daysUntilExpiration(?DateTimeImmutable $asOf = null): int
    {
        $referenceDate = $asOf ?? new DateTimeImmutable();
        $referenceDate = $referenceDate->setTime(0, 0, 0);
        
        $diff = $referenceDate->diff($this->value);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Get expiration status.
     * 
     * @return string 'expired' | 'expiring_soon' | 'ok'
     */
    public function getStatus(int $warningDays = 30, ?DateTimeImmutable $asOf = null): string
    {
        if ($this->isExpired($asOf)) {
            return 'expired';
        }
        
        if ($this->expiresWithinDays($warningDays, $asOf)) {
            return 'expiring_soon';
        }
        
        return 'ok';
    }

    public function equals(ExpirationDate $other): bool
    {
        return $this->value->format('Y-m-d') === $other->value->format('Y-m-d');
    }

    public function isBefore(ExpirationDate $other): bool
    {
        return $this->value < $other->value;
    }

    public function isAfter(ExpirationDate $other): bool
    {
        return $this->value > $other->value;
    }

    public function __toString(): string
    {
        return $this->format('Y-m-d');
    }
}
