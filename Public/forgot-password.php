<?php
/**
 * Forgot Password - Physical file
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/user_helpers.php';

// Handle POST requests (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $controller = getUserController();
        $result = $controller->forgotPassword();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle GET requests (View)
require_once __DIR__ . '/../view/entrance/forgot-password.php';