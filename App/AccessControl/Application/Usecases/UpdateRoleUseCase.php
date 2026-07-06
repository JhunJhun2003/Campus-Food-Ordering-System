<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Entities\Role;
use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class UpdateRoleUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $roleId, string $name): bool
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }

        $role = $this->repository->getRoleById($roleId);
        if (!$role) {
            throw new \RuntimeException("Role with ID {$roleId} not found");
        }

        $existingRole = $this->repository->getRoleByName($name);
        if ($existingRole && $existingRole->getId() !== $roleId) {
            throw new \RuntimeException("Role '{$name}' already exists");
        }

        $updatedRole = new Role($roleId, $name);
        return $this->repository->updateRole($updatedRole);
    }
}