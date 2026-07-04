<?php
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
        header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
    } else {
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    }
} else {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
}
exit();
?>