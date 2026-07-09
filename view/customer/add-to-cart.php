<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/auth_helper.php';
require_once __DIR__ . '/../../inc/user_helpers.php';   // ✅ Add this
require_once __DIR__ . '/../../inc/order_helpers.php';  // ✅ Add this

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

// Check if email is verified
if (!isset($_SESSION['user_verified']) || !$_SESSION['user_verified']) {
    echo json_encode(['success' => false, 'message' => 'Please verify your email first. Check your inbox for the verification code.']);
    exit();
}

// Check if user has permission to add to cart
if (!userHasPermission('add_to_cart')) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to add items to cart.']);
    exit();
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use helper - NO 'new' keyword!
$userController = getUserController();

// Get POST data
$foodId = (int) ($_POST['food_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

if ($foodId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid food item.']);
    exit();
}

// Get user ID
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

// ============================================
// 3. RESPONSE
// ============================================

try {
    // ✅ Use helper for cart controller
    $cartController = getCartController();
    $result = $cartController->add($userId, $foodId, $quantity);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'item_count' => $result['item_count'] ?? 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to add item to cart.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}