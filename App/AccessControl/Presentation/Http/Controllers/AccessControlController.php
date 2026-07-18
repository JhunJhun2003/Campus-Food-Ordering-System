<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Controllers;

use App\AccessControl\Application\Usecases\GetAllRolesUseCase;
use App\AccessControl\Application\Usecases\GetAllPermissionsUseCase;
use App\AccessControl\Application\Usecases\AssignRoleToUserUseCase;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use App\AccessControl\Application\Usecases\CreateRoleUseCase;
use App\AccessControl\Application\Usecases\UpdateRoleUseCase;
use App\AccessControl\Application\Usecases\DeleteRoleUseCase;
use App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase;
use App\Shared\Presentation\Http\Controllers\BaseController;

class AccessControlController extends BaseController
{
    private GetAllRolesUseCase $getAllRolesUseCase;
    private GetAllPermissionsUseCase $getAllPermissionsUseCase;
    private AssignRoleToUserUseCase $assignRoleToUserUseCase;
    private CheckPermissionUseCase $checkPermissionUseCase;
    private CreateRoleUseCase $createRoleUseCase;
    private UpdateRoleUseCase $updateRoleUseCase;
    private DeleteRoleUseCase $deleteRoleUseCase;
    private SyncRolePermissionsUseCase $syncRolePermissionsUseCase;

    public function __construct(
        GetAllRolesUseCase $getAllRolesUseCase,
        GetAllPermissionsUseCase $getAllPermissionsUseCase,
        AssignRoleToUserUseCase $assignRoleToUserUseCase,
        CheckPermissionUseCase $checkPermissionUseCase,
        CreateRoleUseCase $createRoleUseCase,
        UpdateRoleUseCase $updateRoleUseCase,
        DeleteRoleUseCase $deleteRoleUseCase,
        SyncRolePermissionsUseCase $syncRolePermissionsUseCase
    ) {
        parent::__construct();
        $this->getAllRolesUseCase = $getAllRolesUseCase;
        $this->getAllPermissionsUseCase = $getAllPermissionsUseCase;
        $this->assignRoleToUserUseCase = $assignRoleToUserUseCase;
        $this->checkPermissionUseCase = $checkPermissionUseCase;
        $this->createRoleUseCase = $createRoleUseCase;
        $this->updateRoleUseCase = $updateRoleUseCase;
        $this->deleteRoleUseCase = $deleteRoleUseCase;
        $this->syncRolePermissionsUseCase = $syncRolePermissionsUseCase;
    }

    /**
     * Index - Admin only
     */
    public function index()
    {
        // ✅ Check if user is authenticated
        $this->requireAuthentication();
        
        // ✅ Check permission - allow settings managers to manage roles
        $this->authorizeAny(['manage_roles', 'manage_settings']);

        try {
            $roles = $this->getAllRolesUseCase->execute();
            $permissions = $this->getAllPermissionsUseCase->execute();
            
            // Group permissions by module
            $groupedPermissions = [];
            foreach ($permissions as $permission) {
                $module = $permission['module'];
                if (!isset($groupedPermissions[$module])) {
                    $groupedPermissions[$module] = [];
                }
                $groupedPermissions[$module][] = $permission;
            }

            return [
                'roles' => $roles,
                'permissions' => $permissions,
                'groupedPermissions' => $groupedPermissions
            ];
            
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    /**
     * Create Role - Admin only
     */
    public function createRole()
    {
        $this->requireAuthentication();
        $this->authorizeAny(['manage_roles', 'manage_settings']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            
            try {
                $roleId = $this->createRoleUseCase->execute($name);
                $_SESSION['success'] = "Role '{$name}' created successfully";
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            }
        }
    }

    /**
     * Update Role - Admin only
     */
    public function updateRole()
    {
        $this->requireAuthentication();
        $this->authorizeAny(['manage_roles', 'manage_settings']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            
            try {
                $this->updateRoleUseCase->execute($roleId, $name);
                $_SESSION['success'] = "Role updated successfully";
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            }
        }
    }

    /**
     * Delete Role - Admin only
     */
    public function deleteRole()
    {
        $this->requireAuthentication();
        $this->authorizeAny(['manage_roles', 'manage_settings']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            
            try {
                $this->deleteRoleUseCase->execute($roleId);
                $_SESSION['success'] = "Role deleted successfully";
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            }
        }
    }

    /**
     * Assign Role to User - Admin only
     */
    public function assignRole()
    {
        $this->requireAuthentication();
        $this->authorize('manage_roles');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $roleId = (int) ($_POST['role_id'] ?? 0);

            try {
                $this->assignRoleToUserUseCase->execute($userId, $roleId);
                $_SESSION['success'] = "Role assigned to user successfully";
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            }
        }
    }

    /**
     * Sync Permissions - Admin only
     */
    public function syncPermissions()
    {
        $this->requireAuthentication();
        $this->authorizeAny(['manage_roles', 'manage_settings']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionIds = isset($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];

            try {
                $this->syncRolePermissionsUseCase->execute($roleId, $permissionIds);
                $_SESSION['success'] = "Permissions synced successfully";
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-settings.php?tab=access-control');
                exit;
            }
        }
    }

    /**
     * Get Role Permissions (AJAX) - Admin only
     */
    public function getRolePermissions()
    {
        header('Content-Type: application/json');
        
        try {
            $this->requireAuthentication();
            $this->authorizeAny(['manage_roles', 'manage_settings']);

            $roleId = isset($_GET['role_id']) ? (int) $_GET['role_id'] : 0;
            
            if ($roleId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid role ID']);
                exit;
            }

            $roles = $this->getAllRolesUseCase->execute();
            $roleData = null;
            
            foreach ($roles as $role) {
                if ($role['id'] === $roleId) {
                    $roleData = $role;
                    break;
                }
            }
            
            if (!$roleData) {
                echo json_encode(['success' => false, 'error' => 'Role not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'permissions' => $roleData['permissions'] ?? []
            ]);
            exit;
            
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}