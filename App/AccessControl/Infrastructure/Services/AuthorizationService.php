<?php
declare(strict_types=1);

namespace App\AccessControl\Infrastructure\Services;

use App\AccessControl\Domain\Services\AuthorizationServiceInterface;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use Inc\Database;

class AuthorizationService implements AuthorizationServiceInterface
{
    private AccessControlRepository $repository;

    public function __construct()
    {
        $this->repository = new AccessControlRepository(Database::getConnection());
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        if ($userId <= 0) {
            return false;
        }

        // Admin has all permissions
        if ($this->isAdmin($userId)) {
            return true;
        }

        try {
            return $this->repository->hasPermission($userId, $permission);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $permission)) {
                return false;
            }
        }
        return true;
    }

    public function authorize(int $userId, string $permission): void
    {
        if (!$this->hasPermission($userId, $permission)) {
            throw new \RuntimeException(
                "Permission denied. You need '{$permission}' permission.",
                403
            );
        }
    }

    public function authorizeAny(int $userId, array $permissions): void
    {
        if (!$this->hasAnyPermission($userId, $permissions)) {
            throw new \RuntimeException(
                "Permission denied. You need one of: " . implode(', ', $permissions),
                403
            );
        }
    }

    public function authorizeAll(int $userId, array $permissions): void
    {
        if (!$this->hasAllPermissions($userId, $permissions)) {
            throw new \RuntimeException(
                "Permission denied. You need all of: " . implode(', ', $permissions),
                403
            );
        }
    }

    public function isAdmin(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            $roles = $this->repository->getUserRoles($userId);
            foreach ($roles as $role) {
                if ($role->getId() === 1 || $role->getName() === 'admin') {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isStaff(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            $roles = $this->repository->getUserRoles($userId);
            foreach ($roles as $role) {
                if ($role->getId() === 2 || $role->getName() === 'staff') {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCurrentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public function isResourceOwner(int $resourceUserId): bool
    {
        return $this->getCurrentUserId() === $resourceUserId;
    }

    public function authorizeResource(int $resourceUserId, string $permission = 'view_orders'): void
    {
        $currentUserId = $this->getCurrentUserId();

        // If user owns the resource, allow access
        if ($currentUserId === $resourceUserId) {
            return;
        }

        // Otherwise check permission
        $this->authorize($currentUserId, $permission);
    }

    public function getUserRole(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            $roles = $this->repository->getUserRoles($userId);
            if (!empty($roles)) {
                return $roles[0]->getName();
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}