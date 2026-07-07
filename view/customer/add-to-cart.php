<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/auth_helper.php';

use App\User\Presentation\Http\Controllers\UserController;
use App\Cart\Presentation\Http\Controllers\CartController;

header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

// Check if user has permission to add to cart
if (!userHasPermission('add_to_cart')) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to add items to cart.']);
    exit();
}
// Check if user is logged in
$userController = new UserController();
if (!$userController->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

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

try {
    // Add to cart
    $cartController = new CartController();
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