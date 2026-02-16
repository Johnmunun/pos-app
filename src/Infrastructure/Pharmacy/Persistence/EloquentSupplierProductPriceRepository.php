<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\SupplierProductPrice;
use Src\Domain\Pharmacy\Repositories\SupplierProductPriceRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\TaxRate;
use Src\Infrastructure\Pharmacy\Models\SupplierProductPriceModel;

/**
 * Repository: EloquentSupplierProductPriceRepository
 *
 * Implémentation Eloquent du repository des prix fournisseur-produit.
 */
final class EloquentSupplierProductPriceRepository implements SupplierProductPriceRepositoryInterface
{
    public function save(SupplierProductPrice $price): void
    {
        SupplierProductPriceModel::create([
            'id' => $price->getId(),
            'supplier_id' => $price->getSupplierId(),
            'product_id' => $price->getProductId(),
            'normal_price' => $price->getNormalPrice(),
            'agreed_price' => $price->getAgreedPrice(),
            'tax_rate' => $price->getTaxRate()->getValue(),
            'effective_from' => $price->getEffectiveFrom(),
            'is_active' => $price->isActive(),
        ]);
    }

    public function update(SupplierProductPrice $price): void
    {
        SupplierProductPriceModel::where('id', $price->getId())
            ->update([
                'normal_price' => $price->getNormalPrice(),
                'agreed_price' => $price->getAgreedPrice(),
                'tax_rate' => $price->getTaxRate()->getValue(),
                'effective_from' => $price->getEffectiveFrom(),
                'is_active' => $price->isActive(),
                'updated_at' => $price->getUpdatedAt(),
            ]);
    }

    public function findById(string $id): ?SupplierProductPrice
    {
        $model = SupplierProductPriceModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findBySupplierAndProduct(string $supplierId, string $productId): ?SupplierProductPrice
    {
        /** @var SupplierProductPriceModel|null $model */
        $model = SupplierProductPriceModel::query()
            ->where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @return SupplierProductPrice[]
     */
    public function findBySupplier(string $supplierId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierProductPriceModel> $models */
        $models = SupplierProductPriceModel::query()
            ->where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn (SupplierProductPriceModel $model) => $this->toDomainEntity($model))->all();
    }

    /**
     * @return SupplierProductPrice[]
     */
    public function findByProduct(string $productId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierProductPriceModel> $models */
        $models = SupplierProductPriceModel::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('effective_from', 'desc')
            ->get();

        return $models->map(fn (SupplierProductPriceModel $model) => $this->toDomainEntity($model))->all();
    }

    /**
     * @return SupplierProductPrice[]
     */
    public function findHistoryBySupplierAndProduct(string $supplierId, string $productId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierProductPriceModel> $models */
        $models = SupplierProductPriceModel::query()
            ->where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->orderBy('effective_from', 'desc')
            ->get();

        return $models->map(fn (SupplierProductPriceModel $model) => $this->toDomainEntity($model))->all();
    }

    public function delete(string $id): void
    {
        SupplierProductPriceModel::where('id', $id)->delete();
    }

    private function toDomainEntity(SupplierProductPriceModel $model): SupplierProductPrice
    {
        return SupplierProductPrice::reconstitute(
            $model->id,
            $model->supplier_id,
            $model->product_id,
            (float) $model->normal_price,
            $model->agreed_price !== null ? (float) $model->agreed_price : null,
            new TaxRate((float) $model->tax_rate),
            new DateTimeImmutable($model->effective_from->toDateTimeString()),
            (bool) $model->is_active,
            new DateTimeImmutable($model->created_at->toDateTimeString()),
            new DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}
