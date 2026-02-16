<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Persistence;

use DateTimeImmutable;
use Src\Domain\Pharmacy\Entities\Customer;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\CustomerType;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;
use Src\Infrastructure\Pharmacy\Models\CustomerModel;

/**
 * Repository: EloquentCustomerRepository
 *
 * Implémentation Eloquent du repository des clients.
 */
final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function save(Customer $customer): void
    {
        CustomerModel::create([
            'id' => $customer->getId(),
            'shop_id' => $customer->getShopId(),
            'name' => $customer->getName(),
            'phone' => $customer->getPhone()->getValue(),
            'email' => $customer->getEmail()->getValue(),
            'address' => $customer->getAddress(),
            'customer_type' => $customer->getCustomerType()->getValue(),
            'tax_number' => $customer->getTaxNumber(),
            'credit_limit' => $customer->getCreditLimit(),
            'status' => $customer->getStatus(),
        ]);
    }

    public function update(Customer $customer): void
    {
        CustomerModel::where('id', $customer->getId())
            ->update([
                'name' => $customer->getName(),
                'phone' => $customer->getPhone()->getValue(),
                'email' => $customer->getEmail()->getValue(),
                'address' => $customer->getAddress(),
                'customer_type' => $customer->getCustomerType()->getValue(),
                'tax_number' => $customer->getTaxNumber(),
                'credit_limit' => $customer->getCreditLimit(),
                'status' => $customer->getStatus(),
                'updated_at' => $customer->getUpdatedAt(),
            ]);
    }

    public function findById(string $id): ?Customer
    {
        $model = CustomerModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByNameInShop(string $name, int $shopId): ?Customer
    {
        /** @var CustomerModel|null $model */
        $model = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    /**
     * @return Customer[]
     */
    public function findByShop(int $shopId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CustomerModel> $models */
        $models = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->orderBy('name')
            ->get();

        return $models->map(fn (CustomerModel $model) => $this->toDomainEntity($model))->all();
    }

    /**
     * @return Customer[]
     */
    public function findActiveByShop(int $shopId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CustomerModel> $models */
        $models = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return $models->map(fn (CustomerModel $model) => $this->toDomainEntity($model))->all();
    }

    /**
     * @return Customer[]
     */
    public function search(int $shopId, string $query): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CustomerModel> $models */
        $models = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();

        return $models->map(fn (CustomerModel $model) => $this->toDomainEntity($model))->all();
    }

    public function delete(string $id): void
    {
        CustomerModel::where('id', $id)->delete();
    }

    private function toDomainEntity(CustomerModel $model): Customer
    {
        return Customer::reconstitute(
            $model->id,
            (int) $model->shop_id,
            $model->name,
            new SupplierPhone($model->phone),
            new SupplierEmail($model->email),
            $model->address,
            new CustomerType($model->customer_type),
            $model->tax_number,
            $model->credit_limit !== null ? (float) $model->credit_limit : null,
            $model->status,
            new DateTimeImmutable($model->created_at->toDateTimeString()),
            new DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}
