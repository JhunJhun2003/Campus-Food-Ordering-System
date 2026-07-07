<?php
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

// Load route definitions
$routes = require BASE_PATH . '/routes/web.php';

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
    $requestUri = substr($requestUri, 7); // Remove '/Public/'
}

// If empty, set to '/'
if ($requestUri === '' || $requestUri === '/') {
    $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debug logging (remove in production)
error_log('=== Request ===');
error_log('URI: ' . $requestUri);
error_log('Method: ' . $method);

// ============================================
// ROUTE MATCHING
// ============================================

$found = false;
foreach ($routes as $route) {
    // Check if method matches
    if (($route['method'] ?? 'GET') !== $method) {
        continue;
    }

    // Check if pattern matches
    if (preg_match($route['pattern'], $requestUri, $matches)) {
        $found = true;
        // Remove the full match from the beginning
        array_shift($matches);
        // Execute the callback with matched parameters
        call_user_func_array($route['callback'], $matches);
        exit();
    }
}

// ============================================
// NO ROUTE FOUND - HANDLE DEFAULT
// ============================================

if (!$found) {
    error_log('No route found for: ' . $requestUri);
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        // Redirect to appropriate dashboard based on role
        $role = $_SESSION['user_role'] ?? 'user';
        switch ($role) {
            case 'admin':
                header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
                break;
            case 'staff':
                header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
                break;
            default:
                header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
                break;
        }
        exit();
    } else {
        // Show landing page (customer dashboard) for non-logged-in users
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}