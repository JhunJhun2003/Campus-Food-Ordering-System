<?php

namespace App\AccessControl\Infrastructure\Repositories;

use App\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\AccessControl\Domain\Entities\Role;
use App\AccessControl\Domain\Entities\Permission;
use PDO;

class AccessControlRepository implements AccessControlRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ============ ROLE OPERATIONS ============
    
    public function createRole(Role $role): int
    {
        $stmt = $this->db->prepare("INSERT INTO roles (name) VALUES (:name)");
        $stmt->execute([':name' => $role->getName()]);
        return (int) $this->db->lastInsertId();
    }

    public function updateRole(Role $role): bool
    {
        $stmt = $this->db->prepare("UPDATE roles SET name = :name WHERE id = :id");
        return $stmt->execute([
            ':id' => $role->getId(),
            ':name' => $role->getName()
        ]);
    }

    public function deleteRole(int $roleId): bool
    {
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // First remove all role-permission associations
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute([':role_id' => $roleId]);
            
            // Then delete the role
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = :id");
            $stmt->execute([':id' => $roleId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRoleById(int $roleId): ?Role
    {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   GROUP_CONCAT(DISTINCT p.id) as permission_ids,
                   GROUP_CONCAT(DISTINCT p.name) as permission_names,
                   GROUP_CONCAT(DISTINCT p.display_name) as permission_display_names,
                   GROUP_CONCAT(DISTINCT p.module) as permission_modules
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            LEFT JOIN permissions p ON rp.permission_id = p.id
            WHERE r.id = :id
            GROUP BY r.id
        ");
        $stmt->execute([':id' => $roleId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $permissions = $this->formatPermissionsFromData($data);
        
        return new Role(
            (int) $data['id'],
            $data['name'],
            $data['created_at'],
            $data['updated_at'],
            $permissions
        );
    }

    public function getRoleByName(string $name): ?Role
    {
        $stmt = $this->db->prepare("SELECT id, name, created_at, updated_at FROM roles WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Role(
            (int) $data['id'],
            $data['name'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    public function getAllRoles(): array
    {
        $stmt = $this->db->query("SELECT id, name, created_at, updated_at FROM roles ORDER BY id");
        $roles = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $role = new Role(
                (int) $data['id'],
                $data['name'],
                $data['created_at'],
                $data['updated_at']
            );
            $roles[] = $role;
        }
        return $roles;
    }

    public function getRolesByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.name, r.created_at, r.updated_at 
            FROM roles r
            JOIN users u ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $roles = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = new Role(
                (int) $data['id'],
                $data['name'],
                $data['created_at'],
                $data['updated_at']
            );
        }
        return $roles;
    }

    // ============ PERMISSION OPERATIONS ============
    
    public function createPermission(Permission $permission): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO permissions (name, display_name, module) 
            VALUES (:name, :display_name, :module)
        ");
        $stmt->execute([
            ':name' => $permission->getName(),
            ':display_name' => $permission->getDisplayName(),
            ':module' => $permission->getModule()
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updatePermission(Permission $permission): bool
    {
        $stmt = $this->db->prepare("
            UPDATE permissions 
            SET name = :name, display_name = :display_name, module = :module 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $permission->getId(),
            ':name' => $permission->getName(),
            ':display_name' => $permission->getDisplayName(),
            ':module' => $permission->getModule()
        ]);
    }

    public function deletePermission(int $permissionId): bool
    {
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Remove from role_permissions first
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE permission_id = :permission_id");
            $stmt->execute([':permission_id' => $permissionId]);
            
            // Then delete the permission
            $stmt = $this->db->prepare("DELETE FROM permissions WHERE id = :id");
            $stmt->execute([':id' => $permissionId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getPermissionById(int $permissionId): ?Permission
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE id = :id");
        $stmt->execute([':id' => $permissionId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Permission(
            (int) $data['id'],
            $data['name'],
            $data['display_name'],
            $data['module'],
            $data['created_at']
        );
    }

    public function getPermissionByName(string $name): ?Permission
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Permission(
            (int) $data['id'],
            $data['name'],
            $data['display_name'],
            $data['module'],
            $data['created_at']
        );
    }

    public function getAllPermissions(): array
    {
        $stmt = $this->db->query("SELECT * FROM permissions ORDER BY module, display_name");
        $permissions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = new Permission(
                (int) $data['id'],
                $data['name'],
                $data['display_name'],
                $data['module'],
                $data['created_at']
            );
        }
        return $permissions;
    }

    public function getPermissionsByRoleId(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.* 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.module, p.display_name
        ");
        $stmt->execute([':role_id' => $roleId]);
        $permissions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = new Permission(
                (int) $data['id'],
                $data['name'],
                $data['display_name'],
                $data['module'],
                $data['created_at']
            );
        }
        return $permissions;
    }

    public function getPermissionsByModule(string $module): array
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE module = :module ORDER BY display_name");
        $stmt->execute([':module' => $module]);
        $permissions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = new Permission(
                (int) $data['id'],
                $data['name'],
                $data['display_name'],
                $data['module'],
                $data['created_at']
            );
        }
        return $permissions;
    }

    // ============ ROLE-PERMISSION ASSIGNMENTS ============
    
    public function assignPermissionToRole(int $roleId, int $permissionId): bool
    {
        // Check if already assigned
        $stmt = $this->db->prepare("
            SELECT * FROM role_permissions 
            WHERE role_id = :role_id AND permission_id = :permission_id
        ");
        $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
        if ($stmt->fetch()) {
            return true; // Already assigned
        }

        $stmt = $this->db->prepare("
            INSERT INTO role_permissions (role_id, permission_id) 
            VALUES (:role_id, :permission_id)
        ");
        return $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
    }

    public function removePermissionFromRole(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM role_permissions 
            WHERE role_id = :role_id AND permission_id = :permission_id
        ");
        return $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): bool
    {
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Remove all existing permissions
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute([':role_id' => $roleId]);
            
            // Add new permissions
            if (!empty($permissionIds)) {
                $values = [];
                $params = [];
                foreach ($permissionIds as $index => $permId) {
                    $values[] = "(:role_id_{$index}, :perm_id_{$index})";
                    $params[":role_id_{$index}"] = $roleId;
                    $params[":perm_id_{$index}"] = $permId;
                }
                
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES " . implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ============ USER-ROLE ASSIGNMENTS ============
    
    public function assignRoleToUser(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET role_id = :role_id WHERE id = :user_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function removeRoleFromUser(int $userId, int $roleId): bool
    {
        // Check if user has this role
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :user_id AND role_id = :role_id");
        $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
        if (!$stmt->fetch()) {
            return false;
        }

        // Set to default role (user role = 3)
        $stmt = $this->db->prepare("UPDATE users SET role_id = 3 WHERE id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    // ============ PERMISSION CHECKS ============
    
    public function hasPermission(int $userId, string $permissionName): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = :user_id AND p.name = :permission_name
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':permission_name' => $permissionName
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'] > 0;
    }

    public function getUserPermissions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.* 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = :user_id
            ORDER BY p.module, p.display_name
        ");
        $stmt->execute([':user_id' => $userId]);
        $permissions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = new Permission(
                (int) $data['id'],
                $data['name'],
                $data['display_name'],
                $data['module'],
                $data['created_at']
            );
        }
        return $permissions;
    }

    public function getUserRoles(int $userId): array
    {
        return $this->getRolesByUserId($userId);
    }

    // ============ HELPER METHODS ============
    
    private function formatPermissionsFromData(array $data): array
    {
        $permissions = [];
        if ($data['permission_ids'] !== null) {
            $ids = explode(',', $data['permission_ids']);
            $names = explode(',', $data['permission_names']);
            $displayNames = explode(',', $data['permission_display_names']);
            $modules = explode(',', $data['permission_modules']);

            for ($i = 0; $i < count($ids); $i++) {
                $permissions[] = [
                    'id' => (int) $ids[$i],
                    'name' => $names[$i],
                    'display_name' => $displayNames[$i] ?? $names[$i],
                    'module' => $modules[$i] ?? 'general'
                ];
            }
        }
        return $permissions;
    }
}