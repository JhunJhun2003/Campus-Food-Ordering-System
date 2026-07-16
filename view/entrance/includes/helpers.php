<?php
/**
 * Entrance Page Helpers
 * Common functions for login, register, and verification pages
 */

function redirectIfLoggedIn(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../../inc/Database.php';
    require_once __DIR__ . '/../../../inc/access_control_helper.php';

    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        if (isAdminLike((int) $_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
            exit();
        }

        if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'staff') {
            header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
            exit();
        }

        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}

function hasAdminRedirectPermissions(int $userId): bool
{
    try {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        $db = \Inc\Database::getConnection();
        $repository = new \App\AccessControl\Infrastructure\Repositories\AccessControlRepository($db);
        $checkPermission = new \App\AccessControl\Application\Usecases\CheckPermissionUseCase($repository);

        $adminPermissions = [
            'view_dashboard',
            'manage_users',
            'manage_menu',
            'manage_orders',
            'manage_settings',
            'view_reports',
        ];

        foreach ($adminPermissions as $permission) {
            if ($checkPermission->execute($userId, $permission)) {
                return true;
            }
        }
    } catch (\Throwable $e) {
        // ignore permission check failures
    }
    return false;
}

function getErrorMessage(): string
{
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);
    return $error;
}

function getSuccessMessage(): string
{
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['success']);
    return $success;
}

function setErrorMessage(string $message): void
{
    $_SESSION['error'] = $message;
}

function setSuccessMessage(string $message): void
{
    $_SESSION['success'] = $message;
}

function getVerificationSuccess(): string
{
    $success = $_SESSION['verification_success'] ?? '';
    unset($_SESSION['verification_success']);
    return $success;
}

function setVerificationSuccess(string $message): void
{
    $_SESSION['verification_success'] = $message;
}

function getTestCode(): ?string
{
    $code = $_SESSION['test_code'] ?? null;
    unset($_SESSION['test_code']);
    return $code;
}

function setTestCode(string $code): void
{
    $_SESSION['test_code'] = $code;
}