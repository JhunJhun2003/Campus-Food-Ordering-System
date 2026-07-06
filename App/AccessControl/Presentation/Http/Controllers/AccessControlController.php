<?php

namespace App\AccessControl\Presentation\Http\Controllers;

use App\AccessControl\Application\Usecases\GetAllRolesUseCase;
use App\AccessControl\Application\Usecases\GetAllPermissionsUseCase;
use App\AccessControl\Application\Usecases\AssignRoleToUserUseCase;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use App\AccessControl\Application\Usecases\CreateRoleUseCase;
use App\AccessControl\Application\Usecases\UpdateRoleUseCase;
use App\AccessControl\Application\Usecases\DeleteRoleUseCase;
use App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase;

class AccessControlController
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
        $this->getAllRolesUseCase = $getAllRolesUseCase;
        $this->getAllPermissionsUseCase = $getAllPermissionsUseCase;
        $this->assignRoleToUserUseCase = $assignRoleToUserUseCase;
        $this->checkPermissionUseCase = $checkPermissionUseCase;
        $this->createRoleUseCase = $createRoleUseCase;
        $this->updateRoleUseCase = $updateRoleUseCase;
        $this->deleteRoleUseCase = $deleteRoleUseCase;
        $this->syncRolePermissionsUseCase = $syncRolePermissionsUseCase;
    }

    public function index()
    {
        // Check if user has permission
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

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

            require_once __DIR__ . '/../../../../view/admin/access-control.php';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /dashboard');
            exit;
        }
    }

    public function createRole()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            
            try {
                $roleId = $this->createRoleUseCase->execute($name);
                $_SESSION['success'] = "Role '{$name}' created successfully";
                header('Location: /access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /access-control');
                exit;
            }
        }
    }

    public function updateRole()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            
            try {
                $this->updateRoleUseCase->execute($roleId, $name);
                $_SESSION['success'] = "Role updated successfully";
                header('Location: /access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /access-control');
                exit;
            }
        }
    }

    public function deleteRole()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            
            try {
                $this->deleteRoleUseCase->execute($roleId);
                $_SESSION['success'] = "Role deleted successfully";
                header('Location: /access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /access-control');
                exit;
            }
        }
    }

    public function assignRole()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $roleId = (int) ($_POST['role_id'] ?? 0);

            try {
                $this->assignRoleToUserUseCase->execute($userId, $roleId);
                $_SESSION['success'] = "Role assigned to user successfully";
                header('Location: /access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /access-control');
                exit;
            }
        }
    }

    public function syncPermissions()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            header('Location: /dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionIds = isset($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];

            try {
                $this->syncRolePermissionsUseCase->execute($roleId, $permissionIds);
                $_SESSION['success'] = "Permissions synced successfully";
                header('Location: /access-control');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /access-control');
                exit;
            }
        }
    }

    // API endpoint for getting role permissions (AJAX)
    public function getRolePermissions()
    {
        session_start();
        if (!$this->checkPermissionUseCase->execute($_SESSION['user_id'] ?? 0, 'manage_users')) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $roleId = (int) ($_GET['role_id'] ?? 0);
        if ($roleId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role ID']);
            exit;
        }

        try {
            $role = $this->getAllRolesUseCase->execute();
            $roleData = array_filter($role, fn($r) => $r['id'] === $roleId);
            $roleData = reset($roleData);
            
            echo json_encode([
                'success' => true,
                'permissions' => $roleData['permissions'] ?? []
            ]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}