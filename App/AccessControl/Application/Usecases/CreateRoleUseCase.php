<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Entities\Role;
use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class CreateRoleUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(string $name): int
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }

        $existingRole = $this->repository->getRoleByName($name);
        if ($existingRole) {
            throw new \RuntimeException("Role '{$name}' already exists");
        }

        $role = new Role(null, $name);
        return $this->repository->createRole($role);
    }
}