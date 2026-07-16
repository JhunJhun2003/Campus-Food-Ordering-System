<?php
namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use Inc\Database;

class SyncRolePermissionsUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $roleId, array $permissionIds): bool
    {
        $role = $this->repository->getRoleById($roleId);
        if (!$role) {
            throw new \RuntimeException("Role with ID {$roleId} not found");
        }

        return $this->repository->syncRolePermissions($roleId, $permissionIds);
    }
}
