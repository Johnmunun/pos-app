<?php

namespace Src\Infrastructure\Ecommerce\Persistence;

use Src\Domain\Ecommerce\Entities\Customer;
use Src\Domain\Ecommerce\Repositories\CustomerRepositoryInterface;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Src\Shared\ValueObjects\Money;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function save(Customer $customer): void
    {
        CustomerModel::updateOrCreate(
            ['id' => $customer->getId()],
            [
                'shop_id' => $customer->getShopId(),
                'email' => $customer->getEmail(),
                'first_name' => $customer->getFirstName(),
                'last_name' => $customer->getLastName(),
                'phone' => $customer->getPhone(),
                'default_shipping_address' => $customer->getDefaultShippingAddress(),
                'default_billing_address' => $customer->getDefaultBillingAddress(),
                'total_orders' => $customer->getTotalOrders(),
                'total_spent' => $customer->getTotalSpent()->getAmount(),
                'is_active' => $customer->isActive(),
            ]
        );
    }

    public function findById(string $id): ?Customer
    {
        $model = CustomerModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    public function findByEmail(string $shopId, string $email): ?Customer
    {
        $model = CustomerModel::where('shop_id', $shopId)
            ->where('email', $email)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapToEntity($model);
    }

    /**
     * @return Customer[]
     */
    public function findByShop(string $shopId, bool $activeOnly = false): array
    {
        $query = CustomerModel::query()->where('shop_id', $shopId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (CustomerModel $model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function delete(string $id): void
    {
        CustomerModel::where('id', $id)->delete();
    }

    private function mapToEntity(CustomerModel $model): Customer
    {
        $currency = 'USD'; // Default, could be from shop settings

        return new Customer(
            $model->id,
            (string) $model->shop_id,
            $model->email,
            $model->first_name,
            $model->last_name,
            $model->phone,
            $model->default_shipping_address,
            $model->default_billing_address,
            $model->total_orders,
            new Money((float) $model->total_spent, $currency),
            $model->is_active,
            $model->created_at ? \DateTimeImmutable::createFromMutable($model->created_at) : new \DateTimeImmutable(),
            $model->updated_at ? \DateTimeImmutable::createFromMutable($model->updated_at) : new \DateTimeImmutable()
        );
    }
}
