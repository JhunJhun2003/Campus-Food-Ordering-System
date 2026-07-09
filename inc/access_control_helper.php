<?php
declare(strict_types=1);

use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Infrastructure\Services\AuthorizationService;
use App\AccessControl\Application\Usecases\AuthorizeUseCase;
use Inc\Database;

// ============================================
// SERVICE GETTERS
// ============================================

function getAuthorizationService(): AuthorizationService
{
    static $instance = null;
    if ($instance === null) {
        $instance = new AuthorizationService();
    }
    return $instance;
}

function getAuthorizeUseCase(): AuthorizeUseCase
{
    static $instance = null;
    if ($instance === null) {
        $instance = new AuthorizeUseCase(getAuthorizationService());
    }
    return $instance;
}

// ============================================
// PERMISSION CHECK FUNCTIONS
// ============================================

/**
 * Check if current user has a specific permission
 */
function hasPermission(string $permission, ?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        return getAuthorizationService()->hasPermission($userId, $permission);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if current user has any of the given permissions
 */
function hasAnyPermission(array $permissions, ?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        return getAuthorizationService()->hasAnyPermission($userId, $permissions);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if current user has all of the given permissions
 */
function hasAllPermissions(array $permissions, ?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        return getAuthorizationService()->hasAllPermissions($userId, $permissions);
    } catch (\Exception $e) {
        return false;
    }
}

// ============================================
// USER INFO FUNCTIONS
// ============================================

/**
 * Get current user ID from session
 */
function getCurrentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Get current user role
 */
function getCurrentUserRole(): ?string
{
    $userId = getCurrentUserId();
    if ($userId === 0) {
        return null;
    }

    try {
        return getAuthorizationService()->getUserRole($userId);
    } catch (\Exception $e) {
        return null;
    }
}

// ============================================
// ROLE CHECK FUNCTIONS
// ============================================

/**
 * Check if user is admin (with database verification)
 */
function isAdmin(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        return getAuthorizationService()->isAdmin($userId);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if user is staff (with database verification)
 */
function isStaff(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        return getAuthorizationService()->isStaff($userId);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if user is customer
 */
function isCustomer(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    try {
        $role = getAuthorizationService()->getUserRole($userId);
        return $role === 'customer' || $role === 'user';
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get user permissions
 */
function getUserPermissions(?int $userId = null): array
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return [];
    }

    try {
        $repo = new AccessControlRepository(Database::getConnection());
        $permissions = $repo->getUserPermissions($userId);
        return array_map(function($p) {
            return $p->getName();
        }, $permissions);
    } catch (\Exception $e) {
        return [];
    }
}

// ============================================
// REQUIRE FUNCTIONS
// ============================================

/**
 * Require a specific permission
 */
function requirePermission(string $permission, string $redirect = '/dashboard.php'): void
{
    if (!hasPermission($permission)) {
        $_SESSION['error'] = "Permission denied. You need '{$permission}' permission.";
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Require any of the given permissions
 */
function requireAnyPermission(array $permissions, string $redirect = '/dashboard.php'): void
{
    if (!hasAnyPermission($permissions)) {
        $_SESSION['error'] = 'Insufficient permissions.';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Require admin access
 */
function requireAdmin(string $redirect = '/dashboard.php'): void
{
    if (!isAdmin()) {
        $_SESSION['error'] = 'Admin access required.';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Require staff access
 */
function requireStaff(string $redirect = '/dashboard.php'): void
{
    if (!isStaff() && !isAdmin()) {
        $_SESSION['error'] = 'Staff access required.';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Require authentication
 */
function requireAuth(string $redirect = '/entrance/login.php'): void
{
    if (getCurrentUserId() === 0) {
        $_SESSION['error'] = 'Please login to continue.';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Check if user can access a resource
 */
function canAccessResource(int $resourceUserId, string $permission = 'view_orders'): bool
{
    $currentUserId = getCurrentUserId();

    if ($currentUserId === 0) {
        return false;
    }

    if ($currentUserId === $resourceUserId) {
        return true;
    }

    return hasPermission($permission);
}

/**
 * Require resource access
 */
function requireResourceAccess(int $resourceUserId, string $permission = 'view_orders', string $redirect = '/dashboard.php'): void
{
    if (!canAccessResource($resourceUserId, $permission)) {
        $_SESSION['error'] = 'You do not have access to this resource.';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $redirect . '";</script>';
            exit();
        }
        header('Location: ' . $redirect);
        exit();
    }
}

// ============================================
// HELPER FUNCTIONS FOR VIEWS
// ============================================

/**
 * Get all roles
 */
function getAllRoles(): array
{
    try {
        $repo = new AccessControlRepository(Database::getConnection());
        return $repo->getAllRoles();
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Get permissions grouped by module
 */
function getPermissionsGroupedByModule(): array
{
    try {
        $repo = new AccessControlRepository(Database::getConnection());
        $permissions = $repo->getAllPermissions();
        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission->getModule();
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission->toArray();
        }
        return $grouped;
    } catch (\Exception $e) {
        return [];
    }
}