<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Supplier;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use Src\Domain\Pharmacy\ValueObjects\SupplierStatus;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;

/**
 * Repository: EloquentSupplierRepository
 *
 * Implémentation Eloquent du repository des fournisseurs.
 */
final class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    public function save(Supplier $supplier): void
    {
        SupplierModel::create([
            'id' => $supplier->getId(),
            'shop_id' => $supplier->getShopId(),
            'name' => $supplier->getName(),
            'contact_person' => $supplier->getContactPerson(),
            'phone' => $supplier->getPhone()->getValue(),
            'email' => $supplier->getEmail()->getValue(),
            'address' => $supplier->getAddress(),
            'status' => $supplier->getStatus()->getValue(),
        ]);
    }

    public function update(Supplier $supplier): void
    {
        SupplierModel::where('id', $supplier->getId())
            ->update([
                'name' => $supplier->getName(),
                'contact_person' => $supplier->getContactPerson(),
                'phone' => $supplier->getPhone()->getValue(),
                'email' => $supplier->getEmail()->getValue(),
                'address' => $supplier->getAddress(),
                'status' => $supplier->getStatus()->getValue(),
                'updated_at' => $supplier->getUpdatedAt(),
            ]);
    }

    public function findById(string $id): ?Supplier
    {
        $model = SupplierModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByNameInShop(string $name, int $shopId): ?Supplier
    {
        /** @var SupplierModel|null $model */
        $model = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @return Supplier[]
     */
    public function findByShop(int $shopId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierModel> $models */
        $models = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->orderBy('name')
            ->get();

        return $models->map(fn (SupplierModel $model) => $this->toDomainEntity($model))->all();
    }

    /**
     * @return Supplier[]
     */
    public function findActiveByShop(int $shopId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierModel> $models */
        $models = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return $models->map(fn (SupplierModel $model) => $this->toDomainEntity($model))->all();
    }

    public function delete(string $id): void
    {
        SupplierModel::where('id', $id)->delete();
    }

    /**
     * Convertit un model Eloquent en entité Domain.
     */
    private function toDomainEntity(SupplierModel $model): Supplier
    {
        return Supplier::reconstitute(
            $model->id,
            (int) $model->shop_id,
            $model->name,
            $model->contact_person,
            new SupplierPhone($model->phone),
            new SupplierEmail($model->email),
            $model->address,
            new SupplierStatus($model->status),
            new DateTimeImmutable($model->created_at->toDateTimeString()),
            new DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}
