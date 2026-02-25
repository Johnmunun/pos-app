<?php

namespace Src\Infrastructure\Finance\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Finance\Repositories\DebtRepositoryInterface;
use Src\Domain\Finance\Repositories\DebtSettlementRepositoryInterface;
use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;
use Src\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use Src\Domain\Finance\Repositories\ProfitDataProviderInterface;
use Src\Domain\Finance\Services\DebtSettlementService;
use Src\Domain\Finance\Services\InvoiceNumberGeneratorService;
use Src\Domain\Finance\Services\ProfitCalculatorService;
use Src\Infrastructure\Finance\Persistence\EloquentDebtRepository;
use Src\Infrastructure\Finance\Persistence\EloquentDebtSettlementRepository;
use Src\Infrastructure\Finance\Persistence\EloquentExpenseRepository;
use Src\Infrastructure\Finance\Persistence\EloquentInvoiceRepository;
use Src\Infrastructure\Finance\Persistence\EloquentProfitDataProvider;
use Src\Application\Finance\UseCases\Debt\CreateDebtUseCase;
use Src\Application\Finance\UseCases\Debt\SettleDebtUseCase;
use Src\Application\Finance\UseCases\Expense\CreateExpenseUseCase;
use Src\Application\Finance\UseCases\Expense\ListExpensesUseCase;
use Src\Application\Finance\UseCases\Invoice\CreateInvoiceFromSaleUseCase;
use Src\Application\Finance\UseCases\Profit\GenerateProfitReportUseCase;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(ExpenseRepositoryInterface::class, EloquentExpenseRepository::class);
        $this->app->bind(DebtRepositoryInterface::class, EloquentDebtRepository::class);
        $this->app->bind(DebtSettlementRepositoryInterface::class, EloquentDebtSettlementRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(ProfitDataProviderInterface::class, EloquentProfitDataProvider::class);

        // Domain Services
        $this->app->bind(ProfitCalculatorService::class, function () {
            return new ProfitCalculatorService();
        });
        $this->app->bind(InvoiceNumberGeneratorService::class, function ($app) {
            return new InvoiceNumberGeneratorService($app->make(InvoiceRepositoryInterface::class));
        });
        $this->app->bind(DebtSettlementService::class, function ($app) {
            return new DebtSettlementService(
                $app->make(DebtRepositoryInterface::class),
                $app->make(DebtSettlementRepositoryInterface::class)
            );
        });

        // Use Cases
        $this->app->bind(CreateExpenseUseCase::class, function ($app) {
            return new CreateExpenseUseCase($app->make(ExpenseRepositoryInterface::class));
        });
        $this->app->bind(ListExpensesUseCase::class, function ($app) {
            return new ListExpensesUseCase($app->make(ExpenseRepositoryInterface::class));
        });
        $this->app->bind(CreateDebtUseCase::class, function ($app) {
            return new CreateDebtUseCase($app->make(DebtRepositoryInterface::class));
        });
        $this->app->bind(SettleDebtUseCase::class, function ($app) {
            return new SettleDebtUseCase(
                $app->make(DebtRepositoryInterface::class),
                $app->make(DebtSettlementService::class)
            );
        });
        $this->app->bind(CreateInvoiceFromSaleUseCase::class, function ($app) {
            return new CreateInvoiceFromSaleUseCase(
                $app->make(InvoiceRepositoryInterface::class),
                $app->make(InvoiceNumberGeneratorService::class)
            );
        });
        $this->app->bind(GenerateProfitReportUseCase::class, function ($app) {
            return new GenerateProfitReportUseCase(
                $app->make(ProfitDataProviderInterface::class),
                $app->make(ProfitCalculatorService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
