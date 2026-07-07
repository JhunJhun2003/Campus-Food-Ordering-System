<?php
/**
 * Project Root Entry Point
 * Displays the customer dashboard directly to keep a clean root URL, or redirects admin/staff.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: view/admin/admin-dashboard.php');
        exit();
    } elseif ($role === 'staff') {
        header('Location: view/staff/staff-dashboard.php');
        exit();
    }
}

// Directly include the customer dashboard view
require_once __DIR__ . '/view/customer/dashboard.php';
