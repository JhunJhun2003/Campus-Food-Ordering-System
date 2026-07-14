<?php
/**
 * API Endpoint: Request Refund
 * POST /api/refund/request.php
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them
ini_set('log_errors', 1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/refund_helpers.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Please login to request a refund'
        ]);
        exit();
    }

    // Get POST data
    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Validate
    if ($orderId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid order ID'
        ]);
        exit();
    }

    if (empty($reason) || strlen($reason) < 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason (minimum 5 characters)'
        ]);
        exit();
    }

    if (strlen($reason) > 500) {
        echo json_encode([
            'success' => false,
            'message' => 'Reason is too long (maximum 500 characters)'
        ]);
        exit();
    }

    // Get the refund controller
    $refundController = getRefundController();
    
    // Call the requestRefund method
    $result = $refundController->requestRefund();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Refund API Error: " . $e->getMessage());
    error_log("Refund API Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}