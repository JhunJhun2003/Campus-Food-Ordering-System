<?php
/**
 * Verify Reset Code - Physical file
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/user_helpers.php';

header('Content-Type: application/json');

try {
    $controller = getUserController();
    $result = $controller->verifyResetCode();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}