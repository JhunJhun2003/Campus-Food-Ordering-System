<?php
/**
 * Entrance Page Helpers
 * Common functions for login, register, and verification pages
 */

function redirectIfLoggedIn(): void
{
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        $role = $_SESSION['user_role'] ?? 'user';
        $redirectMap = [
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            'user' => '/Campus-Food-Ordering-System/view/customer/dashboard.php',
        ];
        header('Location: ' . ($redirectMap[$role] ?? $redirectMap['user']));
        exit();
    }
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