<?php

namespace Src\Infrastructure\Billing\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Billing\Repositories\BillingPlanRepositoryInterface;
use Src\Infrastructure\Billing\Persistence\DbBillingPlanRepository;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BillingPlanRepositoryInterface::class, DbBillingPlanRepository::class);
    }
}
