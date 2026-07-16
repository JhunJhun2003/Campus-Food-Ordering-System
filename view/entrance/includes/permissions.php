<?php
/**
 * Permissions Helper Functions for Entrance Pages
 */

// ✅ Load auth_helper first
require_once __DIR__ . '/../../../inc/auth_helper.php';

// ============================================
// AUTHENTICATION HELPERS
// ============================================

// ❌ REMOVED: requireEmailVerification() - Already in auth_helper.php
// ❌ REMOVED: requireLogin() - Already in auth_helper.php
// ❌ REMOVED: requirePermission() - Already in auth_helper.php

// ============================================
// ADDITIONAL PERMISSION HELPERS
// ============================================

/**
 * Require specific role
 */
function requireRole(string $role): void
{
    requireLogin();
    
    if (($_SESSION['user_role'] ?? '') !== $role) {
        $_SESSION['error'] = 'You do not have the required role.';
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}

/**
 * Require staff or admin role
 */
function requireStaffAccess(): void
{
    requireLogin();
    
    if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
        $_SESSION['error'] = 'Staff access required.';
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}

/**
 * Require admin-like access
 */
function requireAdminAccess(): void
{
    requireLogin();
    require_once __DIR__ . '/../../../inc/access_control_helper.php';

    if (!isAdminLike((int) ($_SESSION['user_id'] ?? 0))) {
        $_SESSION['error'] = 'Admin access required.';
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}

/**
 * Get customer permissions for the current user
 */
function getCustomerPermissions(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $permissions = [];
    
    // Basic customer permissions
    $customerPermissions = [
        'view_menu',
        'add_to_cart',
        'place_orders',
        'view_orders',
        'update_profile'
    ];
    
    // Staff gets extra permissions
    if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'staff'])) {
        $staffPermissions = [
            'manage_menu',
            'manage_orders',
            'update_order_status',
            'view_dashboard'
        ];
        $permissions = array_merge($customerPermissions, $staffPermissions);
    }
    
    // Admin gets all permissions
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        $adminPermissions = [
            'manage_users',
            'manage_settings',
            'view_reports',
            'manage_roles',
            'manage_permissions'
        ];
        $permissions = array_merge($permissions, $adminPermissions);
    }
    
    return $permissions;
}