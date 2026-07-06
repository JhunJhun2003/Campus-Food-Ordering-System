<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class GetAllRolesUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        $roles = $this->repository->getAllRoles();
        return array_map(function($role) {
            return $role->toArray();
        }, $roles);
    }
}