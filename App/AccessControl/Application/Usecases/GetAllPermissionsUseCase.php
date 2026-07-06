<?php

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;

class GetAllPermissionsUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(): array
    {
        $permissions = $this->repository->getAllPermissions();
        return array_map(function($permission) {
            return $permission->toArray();
        }, $permissions);
    }
}   