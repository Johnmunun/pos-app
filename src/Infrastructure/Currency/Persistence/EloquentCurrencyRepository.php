<?php

namespace Src\Infrastructure\Currency\Persistence;

use App\Models\Currency as CurrencyModel;
use Src\Domain\Currency\Entities\Currency;
use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;
use Src\Domain\Currency\ValueObjects\CurrencyCode;
use Src\Domain\Currency\ValueObjects\CurrencyName;
use Src\Domain\Currency\ValueObjects\CurrencySymbol;

/**
 * Repository Implementation: EloquentCurrencyRepository
 * 
 * Implémentation Eloquent du repository Currency
 */
class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function findById(int $id): ?Currency
    {
        $model = CurrencyModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenantId(int $tenantId): array
    {
        return CurrencyModel::where('tenant_id', $tenantId)
            ->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->get()
            ->map(fn($model) => $this->toEntity($model))
            ->toArray();
    }

    public function findDefaultByTenantId(int $tenantId): ?Currency
    {
        $model = CurrencyModel::where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();
        
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCodeAndTenant(string $code, int $tenantId): ?Currency
    {
        $model = CurrencyModel::where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();
        
        return $model ? $this->toEntity($model) : null;
    }

    public function save(Currency $currency): void
    {
        if ($currency->getId() === 0) {
            // Créer
            $model = new CurrencyModel();
        } else {
            // Mettre à jour
            $model = CurrencyModel::findOrFail($currency->getId());
        }

        $model->tenant_id = $currency->getTenantId();
        $model->code = $currency->getCode()->getValue();
        $model->name = $currency->getName()->getValue();
        $model->symbol = $currency->getSymbol()->getValue();
        $model->is_default = $currency->isDefault();
        $model->is_active = $currency->isActive();
        
        $model->save();

        // Mettre à jour l'ID si c'était une création
        if ($currency->getId() === 0) {
            $reflection = new \ReflectionClass($currency);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($currency, $model->id);
        }
    }

    public function delete(Currency $currency): void
    {
        CurrencyModel::destroy($currency->getId());
    }

    public function unsetAllDefaultsForTenant(int $tenantId): void
    {
        CurrencyModel::where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function toEntity(CurrencyModel $model): Currency
    {
        return new Currency(
            id: $model->id,
            tenantId: $model->tenant_id,
            code: new CurrencyCode($model->code),
            name: new CurrencyName($model->name),
            symbol: new CurrencySymbol($model->symbol),
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active
        );
    }
}
