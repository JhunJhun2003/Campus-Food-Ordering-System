<?php
/**
 * Staff Permission Helper
 * Centralizes permission checks for staff pages
 */

// Fix: Correct path to vendor/autoload.php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/auth_helper.php';
require_once __DIR__ . '/../../../inc/access_control_helper.php';

use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use Inc\Database;

function getStaffPermissions(int $userId): array
{
    $db = Database::getConnection();
    $accessControlRepo = new AccessControlRepository($db);
    $checkPermission = new CheckPermissionUseCase($accessControlRepo);

    return [
        'viewDashboard' => $checkPermission->execute($userId, 'view_dashboard'),
        'viewOrders' => $checkPermission->execute($userId, 'view_orders') || 
                       $checkPermission->execute($userId, 'manage_orders'),
        'viewMenu' => $checkPermission->execute($userId, 'view_menu') || 
                     $checkPermission->execute($userId, 'manage_menu'),
        'manageMenu' => $checkPermission->execute($userId, 'manage_menu'),
        'updateOrderStatus' => $checkPermission->execute($userId, 'update_order_status') || 
                              $checkPermission->execute($userId, 'manage_orders'),
        'updateProfile' => $checkPermission->execute($userId, 'update_profile'),
    ];
}

function requireStaffPermission(bool $hasPermission, string $redirectUrl = '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php'): void
{
    if (!$hasPermission) {
        $_SESSION['error'] = "You do not have permission to access this page.";
        header('Location: ' . $redirectUrl);
        exit();
    }
}

function requireStaffAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        exit();
    }

    if (isAdminLike()) {
        header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
        exit();
    }
    
    if (!in_array($_SESSION['user_role'], ['staff'])) {
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}