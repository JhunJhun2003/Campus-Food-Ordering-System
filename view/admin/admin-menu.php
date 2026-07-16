<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
if (!hasPermission('manage_menu')) {
    renderAdminPermissionDeniedPage('Access denied', 'menu');
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$foodController = getFoodController();

// Handle form submissions with redirect (PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectUrl = 'admin-menu.php';
    $successParam = '';
    
    // Add Food
    if (isset($_POST['add_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=added';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to add food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
    
    // Edit Food
    if (isset($_POST['edit_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=updated';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to update food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
    
    // Delete Food
    if (isset($_POST['delete_food'])) {
        $result = $foodController->handleRequest();
        if ($result['message'] && $result['message']['success']) {
            $successParam = '?success=deleted';
        } else {
            $_SESSION['form_error'] = $result['message']['message'] ?? 'Failed to delete food item';
            $successParam = '?error=1';
        }
        header('Location: ' . $redirectUrl . $successParam);
        exit();
    }
}

// Check for success/error messages from session
$message = null;
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $message = ['success' => true, 'message' => 'Food item added successfully!'];
            break;
        case 'updated':
            $message = ['success' => true, 'message' => 'Food item updated successfully!'];
            break;
        case 'deleted':
            $message = ['success' => true, 'message' => 'Food item deleted successfully!'];
            break;
    }
} elseif (isset($_GET['error']) && isset($_SESSION['form_error'])) {
    $message = ['success' => false, 'message' => $_SESSION['form_error']];
    unset($_SESSION['form_error']);
}

// Get data for display
$foods = $foodController->index();
$categories = $foodController->getCategories();

// Handle edit mode
$editFood = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editFood = $foodController->getForEdit($editId);
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Manage Menu';
$activePage = 'menu';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<?php include __DIR__ . '/component/admin-menu/header.php'; ?>

<!-- Menu Table -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
    <!-- Filter Bar -->
    <?php include __DIR__ . '/component/admin-menu/filter-bar.php'; ?>
    
    <!-- Menu Table -->
    <?php include __DIR__ . '/component/admin-menu/menu-table.php'; ?>
    
    <!-- Pagination -->
    <?php include __DIR__ . '/component/admin-menu/pagination.php'; ?>
</div>

<!-- Add Food Modal -->
<?php include __DIR__ . '/component/admin-menu/add-food-modal.php'; ?>

<!-- Edit Food Modal -->
<?php include __DIR__ . '/component/admin-menu/edit-food-modal.php'; ?>

<!-- Toast -->
<?php include __DIR__ . '/component/admin-menu/toast.php'; ?>

<!-- JavaScript -->
<?php include __DIR__ . '/component/admin-menu/javascript.php'; ?>

</main>
</body>
</html>