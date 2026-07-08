<?php
/**
 * Authentication and Authorization Helper Functions
 * Include this file in pages that need permission checks
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if current user has a specific permission
 */
function userHasPermission(string $permissionName): bool
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // Admin has all permissions
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1) {
        return true;
    }
    
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $db = \Inc\Database::getConnection();
        $repository = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository($db);
        $checkPermission = new \App\AccessControl\Application\Usecases\CheckPermissionUseCase($repository);
        return $checkPermission->execute($_SESSION['user_id'], $permissionName);
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has any of the given permissions
 */
function userHasAnyPermission(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (userHasPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the given permissions
 */
function userHasAllPermissions(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (!userHasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Require a specific permission or redirect
 */
function requirePermission(string $permissionName, string $redirectUrl = '/Campus-Food-Ordering-System/view/customer/dashboard.php'): void
{
    if (!userHasPermission($permissionName)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Require user to be logged in or redirect
 */
function requireLogin(string $redirectUrl = '/Campus-Food-Ordering-System/view/entrance/login.php'): void
{
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Require user to be verified or redirect to verify email page
 */
function requireEmailVerification(): void
{
    if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_verified']) || !$_SESSION['user_verified'])) {
        $_SESSION['error'] = 'Please verify your email first to access this feature.';
        header('Location: /Campus-Food-Ordering-System/view/entrance/verify-email.php');
        exit;
    }
}

/**
 * Get all permissions for current user
 */
function getMyPermissions(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $db = \Inc\Database::getConnection();
        $repository = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository($db);
        $permissions = $repository->getUserPermissions($_SESSION['user_id']);
        return array_map(function($p) {
            return $p->getName();
        }, $permissions);
    } catch (Exception $e) {
        error_log("Error getting user permissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get current user's role
 */
function getCurrentUserRole(): string
{
    return $_SESSION['user_role'] ?? 'user';
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Check if current user is staff
 */
function isStaff(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'staff';
}

/**
 * Check if current user is customer
 */
function isCustomer(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'user';
}