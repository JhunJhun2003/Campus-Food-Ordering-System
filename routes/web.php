<?php
declare(strict_types=1);

// Load all helpers
require_once __DIR__ . '/../inc/order_helpers.php';
require_once __DIR__ . '/../inc/admin_helpers.php';
require_once __DIR__ . '/../inc/user_helpers.php';
require_once __DIR__ . '/../inc/refund_helpers.php';
require_once __DIR__ . '/../inc/access_control_helper.php';  // ✅ Add this if not already in order_helpers.php
require_once __DIR__ . '/../inc/notification_helpers.php';

use App\Kernel\HttpKernel;
use App\Router\Router;
use App\AccessControl\Presentation\Http\Middleware\MiddlewareFactory;

$router = new Router();
// ============================================
// CONTROLLER INSTANCES
// ============================================

$userController = getUserController();
$foodController = getFoodController();
$cartController = getCartController();
$orderController = getOrderController();
$paymentController = getPaymentController();
$refundController = getRefundController();

// ============================================
// HOME ROUTE
// ============================================

$router->get('/', function() {
    require_once __DIR__ . '/../view/customer/dashboard.php';
});

// ============================================
// AUTH ROUTES (Guest only)
// ============================================

$router->get('/login', function() {
    require_once __DIR__ . '/../view/entrance/login.php';
})->withMiddleware(HttpKernel::guest());

$router->post('/login', function($request) use ($userController) {
    $result = $userController->login();
    if ($result['success']) {
        header('Location: ' . $result['redirect']);
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    }
    exit();
})->withMiddleware(HttpKernel::guest());

$router->get('/register', function() {
    require_once __DIR__ . '/../view/entrance/register.php';
})->withMiddleware(HttpKernel::guest());

$router->post('/register', function($request) use ($userController) {
    $result = $userController->register();
    if ($result['success']) {
        $_SESSION['success'] = 'Registration successful! Please login.';
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: /Campus-Food-Ordering-System/view/entrance/register.php');
    }
    exit();
})->withMiddleware(HttpKernel::guest());

$router->get('/verify-email', function() {
    require_once __DIR__ . '/../view/entrance/verify-email.php';
})->withMiddleware(HttpKernel::guest());

$router->post('/verify-email', function($request) {
    require_once __DIR__ . '/../view/entrance/verify-email.php';
})->withMiddleware(HttpKernel::guest());

$router->get('/logout', function() {
    session_destroy();
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
});

// ============================================
// CUSTOMER ROUTES (Auth + Verified)
// ============================================

$router->get('/dashboard', function() {
    require_once __DIR__ . '/../view/customer/dashboard.php';
})->withMiddleware(HttpKernel::customer());

$router->get('/cart', function() {
    require_once __DIR__ . '/../view/customer/cart.php';
})->withMiddleware(HttpKernel::customer());

$router->post('/add-to-cart', function($request) use ($cartController) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first.']);
        exit();
    }
    require_once __DIR__ . '/../view/customer/add-to-cart.php';
})->withMiddleware(HttpKernel::customer());

$router->get('/checkout', function() {
    require_once __DIR__ . '/../view/customer/checkout.php';
})->withMiddleware(HttpKernel::withPermission('place_orders'));

$router->post('/checkout', function($request) {
    require_once __DIR__ . '/../view/customer/checkout.php';
})->withMiddleware(HttpKernel::withPermission('place_orders'));

$router->get('/orders', function() {
    require_once __DIR__ . '/../view/customer/orders.php';
})->withMiddleware(HttpKernel::withPermission('view_orders'));

$router->get('/profile', function() {
    require_once __DIR__ . '/../view/customer/profile.php';
})->withMiddleware(HttpKernel::customer());

// ============================================
// ADMIN ROUTES (Auth + Verified + Admin Role)
// ============================================

$router->get('/admin/dashboard', function() {
    require_once __DIR__ . '/../view/admin/admin-dashboard.php';
})->withMiddleware(HttpKernel::customer());

$router->get('/admin/users', function() {
    require_once __DIR__ . '/../view/admin/admin-users.php';
})->withMiddleware(HttpKernel::withPermission('manage_users'));

$router->get('/admin/menu', function() {
    require_once __DIR__ . '/../view/admin/admin-menu.php';
})->withMiddleware(HttpKernel::withPermission('manage_menu'));

$router->get('/admin/orders', function() {
    require_once __DIR__ . '/../view/admin/admin-orders.php';
})->withMiddleware(HttpKernel::withPermission('manage_orders'));

$router->get('/admin/reports', function() {
    require_once __DIR__ . '/../view/admin/admin-reports.php';
})->withMiddleware(HttpKernel::withPermission('view_reports'));

$router->get('/admin/settings', function() {
    require_once __DIR__ . '/../view/admin/admin-settings.php';
})->withMiddleware(HttpKernel::withPermission('manage_settings'));

$router->get('/admin/profile', function() {
    require_once __DIR__ . '/../view/admin/admin-profile.php';
})->withMiddleware(HttpKernel::withPermission('manage_users'));

// ============================================
// STAFF ROUTES (Auth + Verified + Staff Role)
// ============================================

$router->get('/staff/dashboard', function() {
    require_once __DIR__ . '/../view/staff/staff-dashboard.php';
})->withMiddleware(HttpKernel::staff());

$router->get('/staff/orders', function() {
    require_once __DIR__ . '/../view/staff/staff-orders.php';
})->withMiddleware(HttpKernel::withPermission('manage_orders'));

$router->get('/staff/menu', function() {
    require_once __DIR__ . '/../view/staff/staff-menu.php';
})->withMiddleware(HttpKernel::withPermission('manage_menu'));

$router->get('/staff/profile', function() {
    require_once __DIR__ . '/../view/staff/staff-profile.php';
})->withMiddleware(HttpKernel::staff());

// ============================================
// ACCESS CONTROL ROUTES (Admin only)
// ============================================

$router->post('/access-control/create-role', function($request) {
    $controller = getAccessControlController();
    $controller->createRole();
});

$router->post('/access-control/update-role', function($request) {
    $controller = getAccessControlController();
    $controller->updateRole();
});

$router->post('/access-control/delete-role', function($request) {
    $controller = getAccessControlController();
    $controller->deleteRole();
});

$router->post('/access-control/sync-permissions', function($request) {
    $controller = getAccessControlController();
    $controller->syncPermissions();
});

$router->get('/access-control/get-role-permissions', function($request) {
    $controller = getAccessControlController();
    $controller->getRolePermissions();
});

// ============================================
// API ROUTES (Auth + Verified)
// ============================================

$router->get('/api/cart/count', function($request) use ($cartController) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit();
    }
    try {
        $count = $cartController->getItemCount($_SESSION['user_id']);
        echo json_encode(['count' => $count]);
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    }
    exit();
})->withMiddleware(HttpKernel::customer());

$router->get('/api/payment-methods', function($request) use ($paymentController) {
    header('Content-Type: application/json');
    try {
        $methods = $paymentController->getActiveMethods();
        echo json_encode(['success' => true, 'methods' => $methods]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
});

// ============================================
// REFUND & RECEIPT ROUTES
// ============================================

$router->post('/api/refund/request.php', function($request) use ($refundController) {
    header('Content-Type: application/json');
    echo json_encode($refundController->requestRefund());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->post('/api/refund/approve.php', function($request) use ($refundController) {
    header('Content-Type: application/json');
    echo json_encode($refundController->approveRefund());
    exit();
})->withMiddleware(HttpKernel::withRole(['admin', 'staff']));

$router->post('/api/refund/reject.php', function($request) use ($refundController) {
    header('Content-Type: application/json');
    echo json_encode($refundController->rejectRefund());
    exit();
})->withMiddleware(HttpKernel::withRole(['admin', 'staff']));

$router->get('/admin/refunds', function() {
    require_once __DIR__ . '/../view/admin/admin-refunds.php';
    exit();
})->withMiddleware(HttpKernel::admin());

$router->get('/staff/refunds', function() {
    require_once __DIR__ . '/../view/staff/staff-refunds.php';
    exit();
})->withMiddleware(HttpKernel::staff());

$router->get('/order/receipt', function($request) {
    $orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($orderId <= 0) {
        http_response_code(400);
        echo "Invalid Order ID";
        exit();
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    
    if (!in_array($userRole, ['admin', 'staff'])) {
        $orderRepo = new \App\Order\Infrastructure\Repositories\OrderRepository();
        $order = $orderRepo->findById($orderId);
        if (!$order || $order->getUserId() !== $userId) {
            http_response_code(403);
            echo "Access Denied";
            exit();
        }
    }
    
    $pdfService = new \App\Order\Application\Services\ReceiptPdfService();
    $pdfService->generateReceiptPdf($orderId);
    exit();
})->withMiddleware(HttpKernel::withRole(['admin', 'staff', 'user', 'customer']));

// ============================================
// TEST ROUTES
// ============================================

$router->get('/test/stock/{foodId}', function($foodId, $request) {
    header('Content-Type: application/json');
    try {
        $foodRepo = new \App\Food\Infrastructure\Repositories\FoodRepository();
        $stock = $foodRepo->getStock((int) $foodId);
        $food = $foodRepo->findById((int) $foodId);
        echo json_encode([
            'food_id' => $foodId,
            'name' => $food ? $food->getName() : 'Not found',
            'stock' => $stock
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
});

$router->get('/debug-routes', function() use ($router) {
    echo '<h1>All Registered Routes</h1>';
    echo '<pre>';
    foreach ($router->getRoutes() as $route) {
        echo $route->getMethod() . ' - ' . $route->getPath() . "\n";
    }
    echo '</pre>';
    exit;
});

$router->get('/user/google-login', function() {
    $controller = getUserController();
    $controller->googleLogin();
});

$router->get('/google-callback', function() {
    require_once __DIR__ . '/../Public/google-callback.php';
});

// ============================================
// NOTIFICATION ROUTES
// ============================================

$notificationController = getNotificationController();

$router->get('/api/notifications', function($request) use ($notificationController) {
    header('Content-Type: application/json');
    echo json_encode($notificationController->getNotifications());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->get('/api/notifications/unread-count', function($request) use ($notificationController) {
    header('Content-Type: application/json');
    echo json_encode($notificationController->getUnreadCount());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->post('/api/notifications/mark-read', function($request) use ($notificationController) {
    header('Content-Type: application/json');
    echo json_encode($notificationController->markAsRead());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->post('/api/notifications/mark-all-read', function($request) use ($notificationController) {
    header('Content-Type: application/json');
    echo json_encode($notificationController->markAllAsRead());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->post('/api/notifications/delete', function($request) use ($notificationController) {
    header('Content-Type: application/json');
    echo json_encode($notificationController->delete());
    exit();
})->withMiddleware(HttpKernel::customer());

$router->get('/notifications', function() {
    require_once __DIR__ . '/../view/notifications/index.php';
    exit();
})->withMiddleware(HttpKernel::customer());

// ============================================
// 404 NOT FOUND
// ============================================

$router->get('/{any}', function($any) {
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The page you are looking for does not exist.</p>';
    echo '<a href="/Campus-Food-Ordering-System/">Go Home</a>';
});

$router->get('/debug-routes', function() use ($router) {
    echo '<h1>All Registered Routes</h1>';
    echo '<pre>';
    foreach ($router->getRoutes() as $route) {
        echo $route->getMethod() . ' - ' . $route->getPath() . "\n";
    }
    echo '</pre>';
    echo '<h2>Request Info</h2>';
    echo '<pre>';
    echo 'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    echo 'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
    echo 'PHP_SELF: ' . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
    echo '</pre>';
    exit;
});

// Return the router
return $router;