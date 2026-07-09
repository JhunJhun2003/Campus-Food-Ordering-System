<?php

use App\AccessControl\Presentation\Http\Controllers\AccessControlController;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use Inc\Database;

function getAccessControlController(): AccessControlController
{
    static $instance = null;
    
    if ($instance === null) {
        $db = Database::getConnection();
        $accessControlRepo = new AccessControlRepository($db);
        
        $getAllRolesUseCase = new \App\AccessControl\Application\Usecases\GetAllRolesUseCase($accessControlRepo);
        $getAllPermissionsUseCase = new \App\AccessControl\Application\Usecases\GetAllPermissionsUseCase($accessControlRepo);
        $assignRoleToUserUseCase = new \App\AccessControl\Application\Usecases\AssignRoleToUserUseCase($accessControlRepo);
        $checkPermissionUseCase = new \App\AccessControl\Application\Usecases\CheckPermissionUseCase($accessControlRepo);
        $createRoleUseCase = new \App\AccessControl\Application\Usecases\CreateRoleUseCase($accessControlRepo);
        $updateRoleUseCase = new \App\AccessControl\Application\Usecases\UpdateRoleUseCase($accessControlRepo);
        $deleteRoleUseCase = new \App\AccessControl\Application\Usecases\DeleteRoleUseCase($accessControlRepo);
        $syncRolePermissionsUseCase = new \App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase($accessControlRepo);
        
        $instance = new AccessControlController(
            $getAllRolesUseCase,
            $getAllPermissionsUseCase,
            $assignRoleToUserUseCase,
            $checkPermissionUseCase,
            $createRoleUseCase,
            $updateRoleUseCase,
            $deleteRoleUseCase,
            $syncRolePermissionsUseCase
        );
    }
    
    return $instance;
}