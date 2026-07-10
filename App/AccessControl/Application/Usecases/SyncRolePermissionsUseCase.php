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
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Delete old and insert new permissions atomically
            $db->beginTransaction();
            
            $role = $this->repository->getRoleById($roleId);
            if (!$role) {
                throw new \RuntimeException("Role with ID {$roleId} not found");
            }

            // This method should handle the sync with transaction support
            $result = $this->repository->syncRolePermissions($roleId, $permissionIds);
            
            // ✅ All operations succeeded
            $db->commit();
            
            return $result;
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            throw $e;
        }
    }
}