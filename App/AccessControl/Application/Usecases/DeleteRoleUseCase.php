<?php
namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use Inc\Database;

class DeleteRoleUseCase
{
    private AccessControlRepositoryInterface $repository;

    public function __construct(AccessControlRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $roleId): bool
    {


    // var_dump($roleId); // Debugging line to check the value of $roleId  
    // die(); // Stop execution to inspect the output
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Delete role and its permissions
            $db->beginTransaction();
            
            // Check if role exists
            $role = $this->repository->getRoleById($roleId);
            if (!$role) {
                throw new \RuntimeException("Role with ID {$roleId} not found");
            }

            // Prevent deletion of default roles
            if (in_array($roleId, [1, 2, 3])) { // admin, staff, user
                throw new \RuntimeException("Cannot delete default system roles");
            }

            $result = $this->repository->deleteRole($roleId);
            
            if (!$result) {
                throw new \RuntimeException("Failed to delete role");
            }
            
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