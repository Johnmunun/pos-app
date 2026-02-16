<?php

namespace Src\Domain\Pharmacy\Repositories;

use Src\Domain\Pharmacy\Entities\SaleLine;

interface SaleLineRepositoryInterface
{
    public function save(SaleLine $line): void;

    /**
     * @param string $saleId
     * @return SaleLine[]
     */
    public function findBySale(string $saleId): array;

    public function deleteBySale(string $saleId): void;
}

