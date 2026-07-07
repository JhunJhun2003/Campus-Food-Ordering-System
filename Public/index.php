<?php
session_start();

// Load Composer autoloader and route definitions
require_once __DIR__ . '/../vendor/autoload.php';
$routes = require __DIR__ . '/../routes/web.php';

// Get the request URI without query string
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$basePath = '/Campus-Food-Ordering-System';

if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

if ($requestUri === '') {
    $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debug: Log the request
error_log('=== Request ===');
error_log('URI: ' . $requestUri);
error_log('Method: ' . $method);

// Find matching route
$found = false;
foreach ($routes as $route) {
    if (($route['method'] ?? 'GET') !== $method) {
        continue;
    }

    // Check if the route pattern matches
    if (preg_match($route['pattern'], $requestUri, $matches)) {
        $found = true;
        // Remove the full match from the beginning
        array_shift($matches);
        // Call the callback with the matched parameters
        call_user_func_array($route['callback'], $matches);
        exit();
    }
}

// If no route found, redirect to appropriate page
if (!$found) {
    error_log('No route found for: ' . $requestUri);
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
            header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
        } else {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        }
    } else {
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    }
    exit();
}