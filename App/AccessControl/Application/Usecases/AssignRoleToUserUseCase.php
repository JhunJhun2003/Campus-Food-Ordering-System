<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class AssignRoleToUserUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $userId, int $roleId): bool
    {
        // Validate role exists
        $role = $this->repository->getRoleById($roleId);
        if (!$role) {
            throw new \RuntimeException("Role with ID {$roleId} not found");
        }

        return $this->repository->assignRoleToUser($userId, $roleId);
    }
}