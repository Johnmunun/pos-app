<?php

namespace Src\Domain\Pharmacy\Services;

use Src\Domain\Pharmacy\Entities\Batch;
use Src\Domain\Pharmacy\ValueObjects\ExpiryDate;
use DateTimeImmutable;

class ExpiryAlertService
{
    private const WARNING_DAYS = 30;
    private const CRITICAL_DAYS = 7;

    /**
     * Check if batch is expired
     */
    public function isExpired(Batch $batch): bool
    {
        return $batch->getExpiryDate()->isExpired();
    }

    /**
     * Check if batch is expiring soon
     */
    public function isExpiringSoon(Batch $batch, int $days = self::WARNING_DAYS): bool
    {
        return $batch->getExpiryDate()->isExpiringSoon($days);
    }

    /**
     * Get expiry status level
     */
    public function getExpiryStatus(Batch $batch): string
    {
        if ($this->isExpired($batch)) {
            return 'expired';
        }
        
        if ($this->isExpiringSoon($batch, self::CRITICAL_DAYS)) {
            return 'critical';
        }
        
        if ($this->isExpiringSoon($batch, self::WARNING_DAYS)) {
            return 'warning';
        }
        
        return 'good';
    }

    /**
     * Calculate days until expiry
     */
    public function getDaysUntilExpiry(Batch $batch): int
    {
        return $batch->getExpiryDate()->getDaysUntilExpiry();
    }

    /**
     * Get expiry alert message
     */
    public function getExpiryAlertMessage(Batch $batch): string
    {
        $days = $this->getDaysUntilExpiry($batch);
        
        if ($days < 0) {
            return "Expired " . abs($days) . " days ago";
        }
        
        if ($days === 0) {
            return "Expires today";
        }
        
        if ($days === 1) {
            return "Expires tomorrow";
        }
        
        return "Expires in {$days} days";
    }

    /**
     * Check if batch should be quarantined
     */
    public function shouldQuarantine(Batch $batch): bool
    {
        // Quarantine if expiring within 7 days or already expired
        return $this->isExpiringSoon($batch, self::CRITICAL_DAYS) || $this->isExpired($batch);
    }

    /**
     * Validate expiry date
     */
    public function validateExpiryDate(ExpiryDate $expiryDate): void
    {
        $today = new DateTimeImmutable();
        
        if ($expiryDate->getDate() < $today) {
            throw new \InvalidArgumentException('Expiry date cannot be in the past');
        }
        
        $maxDate = $today->add(new \DateInterval('P5Y')); // 5 years maximum
        if ($expiryDate->getDate() > $maxDate) {
            throw new \InvalidArgumentException('Expiry date cannot be more than 5 years in the future');
        }
    }
}