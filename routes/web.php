<?php
/**
 * Web Routes
 * Define all application routes here
 */

// Use controllers
use App\User\Presentation\Http\Controllers\UserController;
use App\Food\Presentation\Http\Controllers\FoodController;
use App\Cart\Presentation\Http\Controllers\CartController;
use App\Order\Presentation\Http\Controllers\OrderController;
use App\Payment\Presentation\Http\Controllers\PaymentController;
use App\AccessControl\Presentation\Http\Controllers\AccessControlController;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use Inc\Database;

// Initialize routes array
$routes = [];

// ============================================
// AUTHENTICATION ROUTES
// ============================================

// Login page
$routes[] = [
    'pattern' => '/^\/login$/',
    'callback' => function() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/entrance/login.php';
    },
    'method' => 'GET'
];

// Login form submission
$routes[] = [
    'pattern' => '/^\/login$/',
    'callback' => function() {
        $controller = new UserController();
        $result = $controller->login();
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        }
        exit();
    },
    'method' => 'POST'
];

// Register page
$routes[] = [
    'pattern' => '/^\/register$/',
    'callback' => function() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/entrance/register.php';
    },
    'method' => 'GET'
];

// Register form submission
$routes[] = [
    'pattern' => '/^\/register$/',
    'callback' => function() {
        $controller = new UserController();
        $result = $controller->register();
        if ($result['success']) {
            $_SESSION['success'] = 'Registration successful! Please login.';
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /Campus-Food-Ordering-System/view/entrance/register.php');
        }
        exit();
    },
    'method' => 'POST'
];

// Logout
$routes[] = [
    'pattern' => '/^\/logout$/',
    'callback' => function() {
        session_destroy();
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        exit();
    },
    'method' => 'GET'
];

// ============================================
// CUSTOMER ROUTES
// ============================================

// Home page
$routes[] = [
    'pattern' => '/^\/$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
        } else if ($_SESSION['user_role'] === 'staff') {
            header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
        } else {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        }
        exit();
    },
    'method' => 'GET'
];

// Customer Dashboard
$routes[] = [
    'pattern' => '/^\/dashboard$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/customer/dashboard.php';
    },
    'method' => 'GET'
];

// Customer Cart
$routes[] = [
    'pattern' => '/^\/cart$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/customer/cart.php';
    },
    'method' => 'GET'
];

// Add to cart (AJAX)
$routes[] = [
    'pattern' => '/^\/add-to-cart$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first.']);
            exit();
        }
        require_once __DIR__ . '/../view/customer/add-to-cart.php';
    },
    'method' => 'POST'
];

// Customer Checkout
$routes[] = [
    'pattern' => '/^\/checkout$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/customer/checkout.php';
    },
    'method' => 'GET'
];

// Checkout form submission
$routes[] = [
    'pattern' => '/^\/checkout$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/customer/checkout.php';
    },
    'method' => 'POST'
];

// Customer Orders
$routes[] = [
    'pattern' => '/^\/orders$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/customer/orders.php';
    },
    'method' => 'GET'
];

// ============================================
// ADMIN ROUTES
// ============================================

// Admin Dashboard
$routes[] = [
    'pattern' => '/^\/admin\/dashboard$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-dashboard.php';
    },
    'method' => 'GET'
];

// Admin Users
$routes[] = [
    'pattern' => '/^\/admin\/users$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-users.php';
    },
    'method' => 'GET'
];

// Admin Menu
$routes[] = [
    'pattern' => '/^\/admin\/menu$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-menu.php';
    },
    'method' => 'GET'
];

// Admin Orders
$routes[] = [
    'pattern' => '/^\/admin\/orders$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-orders.php';
    },
    'method' => 'GET'
];

// Admin Reports
$routes[] = [
    'pattern' => '/^\/admin\/reports$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-reports.php';
    },
    'method' => 'GET'
];

// Admin Settings
$routes[] = [
    'pattern' => '/^\/admin\/settings$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
        require_once __DIR__ . '/../view/admin/admin-settings.php';
    },
    'method' => 'GET'
];

// ============================================
// ACCESS CONTROL ROUTES
// ============================================

// Helper function to initialize AccessControlController
function getAccessControlController() {
    // Import necessary classes inside the function
    require_once __DIR__ . '/../App/AccessControl/Infrastructure/Repositories/AccessControlRepository.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/GetAllRolesUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/GetAllPermissionsUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/AssignRoleToUserUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/CheckPermissionUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/CreateRoleUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/UpdateRoleUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/DeleteRoleUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Application/Usecases/SyncRolePermissionsUseCase.php';
    require_once __DIR__ . '/../App/AccessControl/Presentation/Http/Controllers/AccessControlController.php';
    
    $db = Database::getConnection();
    $accessControlRepo = new App\AccessControl\Infrastructure\Repositories\AccessControlRepository($db);
    
    $getAllRolesUseCase = new App\AccessControl\Application\Usecases\GetAllRolesUseCase($accessControlRepo);
    $getAllPermissionsUseCase = new App\AccessControl\Application\Usecases\GetAllPermissionsUseCase($accessControlRepo);
    $assignRoleToUserUseCase = new App\AccessControl\Application\Usecases\AssignRoleToUserUseCase($accessControlRepo);
    $checkPermissionUseCase = new App\AccessControl\Application\Usecases\CheckPermissionUseCase($accessControlRepo);
    $createRoleUseCase = new App\AccessControl\Application\Usecases\CreateRoleUseCase($accessControlRepo);
    $updateRoleUseCase = new App\AccessControl\Application\Usecases\UpdateRoleUseCase($accessControlRepo);
    $deleteRoleUseCase = new App\AccessControl\Application\Usecases\DeleteRoleUseCase($accessControlRepo);
    $syncRolePermissionsUseCase = new App\AccessControl\Application\Usecases\SyncRolePermissionsUseCase($accessControlRepo);
    
    return new App\AccessControl\Presentation\Http\Controllers\AccessControlController(
        $getAllRolesUseCase,
        $getAllPermissionsUseCase,
        $assignRoleToUserUseCase,
        $checkPermissionUseCase,
        $createRoleUseCase,
        $updateRoleUseCase,
        $deleteRoleUseCase,
        $syncRolePermissionsUseCase
    );
}

// Create Role
$routes[] = [
    'pattern' => '/^\/access-control\/create-role$/',
    'callback' => function() {
        $controller = getAccessControlController();
        $controller->createRole();
    },
    'method' => 'POST'
];

// Update Role
$routes[] = [
    'pattern' => '/^\/access-control\/update-role$/',
    'callback' => function() {
        $controller = getAccessControlController();
        $controller->updateRole();
    },
    'method' => 'POST'
];

// Delete Role
$routes[] = [
    'pattern' => '/^\/access-control\/delete-role$/',
    'callback' => function() {
        $controller = getAccessControlController();
        $controller->deleteRole();
    },
    'method' => 'POST'
];

// Sync Permissions
$routes[] = [
    'pattern' => '/^\/access-control\/sync-permissions$/',
    'callback' => function() {
        $controller = getAccessControlController();
        $controller->syncPermissions();
    },
    'method' => 'POST'
];

// Get Role Permissions (AJAX)
$routes[] = [
    'pattern' => '/^\/access-control\/get-role-permissions$/',
    'callback' => function() {
        $controller = getAccessControlController();
        $controller->getRolePermissions();
    },
    'method' => 'GET'
];

// ============================================
// API ROUTES (AJAX)
// ============================================

// API: Get cart count
$routes[] = [
    'pattern' => '/^\/api\/cart\/count$/',
    'callback' => function() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['count' => 0]);
            exit();
        }
        
        try {
            $cartController = new CartController();
            $count = $cartController->getItemCount($_SESSION['user_id']);
            echo json_encode(['count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
        }
        exit();
    },
    'method' => 'GET'
];

// API: Get payment methods
$routes[] = [
    'pattern' => '/^\/api\/payment-methods$/',
    'callback' => function() {
        header('Content-Type: application/json');
        try {
            $paymentController = new PaymentController();
            $methods = $paymentController->getActiveMethods();
            echo json_encode(['success' => true, 'methods' => $methods]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    },
    'method' => 'GET'
];

// Debug: Show all routes
$routes[] = [
    'pattern' => '/^\/debug-routes$/',
    'callback' => function() use ($routes) {
        echo '<h1>All Registered Routes</h1>';
        echo '<pre>';
        foreach ($routes as $route) {
            echo $route['method'] . ' - ' . $route['pattern'] . "\n";
        }
        echo '</pre>';
        echo '<hr>';
        echo '<h2>Test Access Control Route</h2>';
        echo '<a href="/Campus-Food-Ordering-System/access-control/get-role-permissions?role_id=3">Test get-role-permissions</a>';
        exit;
    },
    'method' => 'GET'
];

// 404 Not Found - Keep this at the END
$routes[] = [
    'pattern' => '/.*/',
    'callback' => function() {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>The page you are looking for does not exist.</p>';
        echo '<a href="/Campus-Food-Ordering-System/">Go Home</a>';
    },
    'method' => 'GET'
];

// Test: Check stock
$routes[] = [
    'pattern' => '/^\/test\/stock\/(\d+)$/',
    'callback' => function($foodId) {
        header('Content-Type: application/json');
        try {
            $foodRepo = new App\Food\Infrastructure\Repositories\FoodRepository();
            $stock = $foodRepo->getStock($foodId);
            $food = $foodRepo->findById($foodId);
            echo json_encode([
                'food_id' => $foodId,
                'name' => $food ? $food->getName() : 'Not found',
                'stock' => $stock
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    },
    'method' => 'GET'
];

// Email Verification Page
$routes[] = [
    'pattern' => '/^\/verify-email$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/entrance/verify-email.php';
    },
    'method' => 'GET'
];

// Email Verification POST
$routes[] = [
    'pattern' => '/^\/verify-email$/',
    'callback' => function() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
        require_once __DIR__ . '/../view/entrance/verify-email.php';
    },
    'method' => 'POST'
];

// Return routes
return $routes;