<?php
declare(strict_types=1);

use Inc\Database;

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
 * Get current user role from session
 */
function getCurrentUserRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

// ============================================
// ROLE CHECK FUNCTIONS - Using simple session checks
// ============================================

/**
 * Check if current user is admin
 */
function isAdmin(?int $userId = null): bool
{
    // If userId is provided and different from current session user
    if ($userId !== null && $userId !== getCurrentUserId()) {
        return isAdminFromDatabase($userId);
    }
    
    // Session check for current user
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user is staff
 */
function isStaff(?int $userId = null): bool
{
    // If userId is provided and different from current session user
    if ($userId !== null && $userId !== getCurrentUserId()) {
        return isStaffFromDatabase($userId);
    }
    
    // Session check for current user
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

/**
 * Check if user is customer
 */
function isCustomer(?int $userId = null): bool
{
    if ($userId !== null && $userId !== getCurrentUserId()) {
        $role = getUserRoleFromDatabase($userId);
        return $role === 'customer' || $role === 'user';
    }
    
    $role = $_SESSION['user_role'] ?? 'user';
    return $role === 'customer' || $role === 'user';
}

/**
 * Check if user is admin from database
 */
function isAdminFromDatabase(int $userId): bool
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role && strtolower($role['name']) === 'admin';
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if user is staff from database
 */
function isStaffFromDatabase(int $userId): bool
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role && strtolower($role['name']) === 'staff';
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get user role from database
 */
function getUserRoleFromDatabase(int $userId): ?string
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role ? $role['name'] : null;
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Check if user is customer only (not admin or staff)
 */
function isCustomerOnly(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    return !isAdmin($userId) && !isStaff($userId);
}

function hasAdminPermissions(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = getCurrentUserId();
    }

    if ($userId === 0) {
        return false;
    }

    return hasAnyPermission([
        'view_dashboard',
        'manage_users',
        'manage_menu',
        'manage_orders',
        'manage_settings',
        'view_reports',
    ], $userId);
}

function isAdminLike(?int $userId = null): bool
{
    if (isAdmin($userId)) {
        return true;
    }

    if (isStaff($userId)) {
        return false;
    }

    return hasAdminPermissions($userId);
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

    // Admin has all permissions
    if (isAdmin($userId)) {
        return true;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = :user_id AND p.name = :permission_name
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':permission_name' => $permission
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'] > 0;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Check if current user has any of the given permissions
 */
function hasAnyPermission(array $permissions, ?int $userId = null): bool
{
    foreach ($permissions as $permission) {
        if (hasPermission($permission, $userId)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if current user has all of the given permissions
 */
function hasAllPermissions(array $permissions, ?int $userId = null): bool
{
    foreach ($permissions as $permission) {
        if (!hasPermission($permission, $userId)) {
            return false;
        }
    }
    return true;
}

// ============================================
// REQUIRE FUNCTIONS
// ============================================

/**
 * Render a friendly access denied page for admin views when permissions are disabled by admin.
 */
function renderAdminPermissionDeniedPage(string $pageTitle = 'Access Denied', string $activePage = 'dashboard', string $message = 'Permissions denied by admin. Please contact the administrator.'): void
{
    $currentUserName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Admin';
    $currentUser = ['name' => $currentUserName];

    include __DIR__ . '/../view/admin/includes/sidebar.php';
    ?>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-lock text-2xl"></i>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Access denied</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($message); ?></p>
        </div>
    </div>
    <?php
    echo '</main></body></html>';
    exit();
}

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
    if (!isAdminLike()) {
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
    if (isAdminLike() || !isStaff()) {
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
 * Redirect admin/staff away from customer pages
 */
function redirectAdminStaffFromCustomer(string $redirectUrl = '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php'): void
{
    if (isAdminLike() || isStaff()) {
        $_SESSION['error'] = 'Access denied. This page is for customers only.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

// ============================================
// RESOURCE ACCESS
// ============================================

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
// MAINTENANCE MODE FUNCTIONS
// ============================================

/**
 * Check if maintenance mode is enabled
 */
function isMaintenanceMode(): bool
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && (int) $result['setting_value'] === 1;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Redirect to maintenance page if maintenance mode is ON and user is not admin
 */
function checkMaintenanceRedirect(): void
{
    // Admin and admin-like users can always access
    if (isAdminLike()) {
        return;
    }
    
    // Check if maintenance is ON
    if (isMaintenanceMode()) {
        $_SESSION['maintenance_message'] = 'The system is currently under maintenance. Please try again later.';
        header('Location: /Campus-Food-Ordering-System/view/maintenance/maintenance.php');
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
        require_once __DIR__ . '/../vendor/autoload.php';
        $repo = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository(Database::getConnection());
        $roles = $repo->getAllRoles();
        return array_map(function($role) {
            if (method_exists($role, 'toArray')) {
                return $role->toArray();
            }
            return (array) $role;
        }, $roles);
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
        require_once __DIR__ . '/../vendor/autoload.php';
        $repo = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository(Database::getConnection());
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
        require_once __DIR__ . '/../vendor/autoload.php';
        $repo = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository(Database::getConnection());
        $permissions = $repo->getUserPermissions($userId);
        return array_map(function($p) {
            return $p->getName();
        }, $permissions);
    } catch (\Exception $e) {
        return [];
    }
}

// ============================================
// ACCESS CONTROL CONTROLLER GETTER
// ============================================

/**
 * Get AccessControlController instance with all dependencies
 */
function getAccessControlController()
{
    static $instance = null;
    
    if ($instance === null) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $db = Database::getConnection();
        $accessControlRepo = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository($db);
        
        $getAllRolesUseCase = new \App\AccessControl\Application\Usecases\GetAllRolesUseCase($accessControlRepo);
        $getAllPermissionsUseCase = new \App\AccessControl\Application\Usecases\GetAllPermissionsUseCase($accessControlRepo);
        $assignRoleToUserUseCase = new \App\AccessControl\Application\Usecases\AssignRoleToUserUseCase($accessControlRepo);
        $checkPermissionUseCase = new \App\AccessControl\Application\Usecases\CheckPermissionUseCase($accessControlRepo);
        $createRoleUseCase = new \App\AccessControl\Application\Usecases\CreateRoleUseCase($accessControlRepo);
        $updateRoleUseCase = new \App\AccessControl\Application\Usecases\UpdateRoleUseCase($accessControlRepo);
        $deleteRoleUseCase = new \App\AccessControl\Application\Usecases\DeleteRoleUseCase($accessControlRepo);
        $syncRolePermissionsUseCase = new \App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase($accessControlRepo);
        
        $instance = new \App\AccessControl\Presentation\Http\Controllers\AccessControlController(
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