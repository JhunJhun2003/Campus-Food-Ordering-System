<?php
/**
 * Debug API Endpoint: Request Refund
 * POST /api/refund/request_debug.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log everything
error_log("=== REFUND REQUEST DEBUG ===");
error_log("POST: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/refund_helpers.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in',
            'debug' => ['session' => $_SESSION]
        ]);
        exit();
    }

    // Get POST data
    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    error_log("Order ID: $orderId, Reason: $reason");

    // Validate
    if ($orderId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid order ID',
            'debug' => ['order_id' => $orderId]
        ]);
        exit();
    }

    if (empty($reason) || strlen($reason) < 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason (minimum 5 characters)',
            'debug' => ['reason' => $reason, 'length' => strlen($reason)]
        ]);
        exit();
    }

    // Get the refund controller
    $refundController = getRefundController();
    
    // Call the requestRefund method
    $result = $refundController->requestRefund();
    
    error_log("Result: " . print_r($result, true));
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}