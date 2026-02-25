<?php

namespace Src\Application\Finance\UseCases\Expense;

use Src\Application\Finance\DTO\CreateExpenseDTO;
use Src\Domain\Finance\Entities\Expense;
use Src\Domain\Finance\Repositories\ExpenseRepositoryInterface;
use Src\Domain\Finance\ValueObjects\ExpenseCategory;
use Src\Shared\ValueObjects\Money;

final class CreateExpenseUseCase
{
    public function __construct(
        private ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function execute(CreateExpenseDTO $dto): Expense
    {
        $category = new ExpenseCategory($dto->category);
        $amount = new Money($dto->amount, $dto->currency);

        $expense = Expense::create(
            $dto->tenantId,
            $dto->shopId,
            $amount,
            $category,
            $dto->description,
            $dto->createdBy,
            $dto->supplierId,
            $dto->attachmentPath,
            $dto->depotId
        );

        $this->expenseRepository->save($expense);
        return $expense;
    }
}
