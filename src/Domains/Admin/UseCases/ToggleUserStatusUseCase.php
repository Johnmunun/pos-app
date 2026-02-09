<?php

namespace Src\Domains\Admin\UseCases;

use Src\Domains\Admin\Repositories\AdminRepositoryInterface;

class ToggleUserStatusUseCase
{
    private AdminRepositoryInterface $repository;

    public function __construct(AdminRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $userId): void
    {
        $user = $this->repository->getUserById($userId);
        if (!$user) {
            throw new \Exception("User not found");
        }
        
        $newStatus = !$user->getStatus();
        $this->repository->updateUserStatus($userId, $newStatus);
    }
}