<?php

namespace App\AccessControl\Domain\Repositories;

use App\AccessControl\Domain\Entities\Role;
use App\AccessControl\Domain\Entities\Permission;

interface AccessControlRepositoryInterface
{
    // Role operations
    public function createRole(Role $role): int;
    public function updateRole(Role $role): bool;
    public function deleteRole(int $roleId): bool;
    public function getRoleById(int $roleId): ?Role;
    public function getRoleByName(string $name): ?Role;
    public function getAllRoles(): array;
    public function getRolesByUserId(int $userId): array;
    
    // Permission operations
    public function createPermission(Permission $permission): int;
    public function updatePermission(Permission $permission): bool;
    public function deletePermission(int $permissionId): bool;
    public function getPermissionById(int $permissionId): ?Permission;
    public function getPermissionByName(string $name): ?Permission;
    public function getAllPermissions(): array;
    public function getPermissionsByRoleId(int $roleId): array;
    public function getPermissionsByModule(string $module): array;
    
    // Role-Permission assignments
    public function assignPermissionToRole(int $roleId, int $permissionId): bool;
    public function removePermissionFromRole(int $roleId, int $permissionId): bool;
    public function syncRolePermissions(int $roleId, array $permissionIds): bool;
    
    // User-Role assignments
    public function assignRoleToUser(int $userId, int $roleId): bool;
    public function removeRoleFromUser(int $userId, int $roleId): bool;
    
    // Permission checks
    public function hasPermission(int $userId, string $permissionName): bool;
    public function getUserPermissions(int $userId): array;
    public function getUserRoles(int $userId): array;
}