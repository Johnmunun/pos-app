<?php

namespace Src\Domain\Pharmacy\ValueObjects;

use InvalidArgumentException;
use DateTimeImmutable;

class ExpiryDate
{
    private DateTimeImmutable $date;

    public function __construct(DateTimeImmutable $date)
    {
        if ($date < new DateTimeImmutable()) {
            throw new InvalidArgumentException('Expiry date cannot be in the past');
        }

        $this->date = $date;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function isExpired(): bool
    {
        return $this->date < new DateTimeImmutable();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        $warningDate = (new DateTimeImmutable())->add(new \DateInterval("P{$days}D"));
        return $this->date <= $warningDate;
    }

    public function getDaysUntilExpiry(): int
    {
        $now = new DateTimeImmutable();
        return $now->diff($this->date)->days;
    }

    public function equals(self $other): bool
    {
        return $this->date == $other->date;
    }

    public function __toString(): string
    {
        return $this->date->format('Y-m-d');
    }
}