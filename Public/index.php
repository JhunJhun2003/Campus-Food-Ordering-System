<?php
declare(strict_types=1);

/**
 * Front Controller - Entry point for all requests
 * Handles routing and request dispatching
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path (project root)
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load helpers
require_once BASE_PATH . '/inc/order_helpers.php';
require_once BASE_PATH . '/inc/admin_helpers.php';
require_once BASE_PATH . '/inc/user_helpers.php';
require_once BASE_PATH . '/inc/access_control_helper.php';

// Load routes
$router = require BASE_PATH . '/routes/web.php';

// ============================================
// REQUEST PROCESSING
// ============================================

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string from URI
$requestUri = strtok($requestUri, '?');

// Remove base path
$basePath = '/Campus-Food-Ordering-System';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remove /Public/ if present
if (strpos($requestUri, '/Public/') === 0) {
    $requestUri = substr($requestUri, strlen('/Public/'));
    if ($requestUri === '' || $requestUri[0] !== '/') {
        $requestUri = '/' . $requestUri;
    }
}

// If empty, set to '/'
if ($requestUri === '' || $requestUri === '/') {
    $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================
// PREPARE REQUEST DATA FOR MIDDLEWARE
// ============================================

$request = [
    'session' => $_SESSION,
    'get' => $_GET,
    'post' => $_POST,
    'files' => $_FILES,
    'server' => $_SERVER,
    'method' => $method,
    'uri' => $requestUri,
];

// ============================================
// DISPATCH ROUTE
// ============================================

$response = $router->dispatch($method, $requestUri, $request);

// If response is an array with error, handle it
if (is_array($response) && isset($response['error'])) {
    http_response_code($response['status'] ?? 404);
    echo $response['error'];
    exit();
}

// If response is a string (HTML content), echo it
if (is_string($response)) {
    echo $response;
    exit();
}

// If response is null or empty, check for redirects
if ($response === null) {
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        $role = $_SESSION['user_role'] ?? 'user';
        $redirectMap = [
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            'user' => '/Campus-Food-Ordering-System/view/customer/dashboard.php',
        ];
        header('Location: ' . ($redirectMap[$role] ?? $redirectMap['user']));
        exit();
    } else {
        require_once __DIR__ . '/../view/customer/dashboard.php';
        exit();
    }
}