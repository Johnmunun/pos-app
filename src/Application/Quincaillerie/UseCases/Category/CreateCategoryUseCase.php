<?php

namespace Src\Application\Quincaillerie\UseCases\Category;

use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Application\Quincaillerie\DTO\CreateCategoryDTO;
use Src\Domain\Quincaillerie\Entities\Category;

/**
 * Cas d'usage création catégorie - Module Quincaillerie.
 */
class CreateCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CreateCategoryDTO $dto): Category
    {
        if ($this->categoryRepository->existsByName($dto->name, $dto->shopId)) {
            throw new \InvalidArgumentException('Une catégorie avec ce nom existe déjà.');
        }

        $category = Category::create(
            $dto->shopId,
            $dto->name,
            $dto->description,
            $dto->parentId,
            $dto->sortOrder
        );

        $this->categoryRepository->save($category);

        return $category;
    }
}
