<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class CheckPermissionUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $userId, string $permissionName): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return $this->repository->hasPermission($userId, $permissionName);
    }
}