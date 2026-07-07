<?php
declare(strict_types=1);

session_start();

// ============================================
// 1. BUSINESS LOGIC - LOGOUT
// ============================================

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ============================================
// 2. REDIRECT TO LOGIN
// ============================================

header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
exit();