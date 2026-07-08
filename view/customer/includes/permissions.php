<?php
/**
 * Customer Permission Helper
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/auth_helper.php';

function getCustomerPermissions(): array
{
    return [
        'viewMenu' => userHasPermission('view_menu'),
        'addToCart' => userHasPermission('add_to_cart'),
        'placeOrders' => userHasPermission('place_orders'),
        'viewOrders' => userHasPermission('view_orders'),
        'updateProfile' => userHasPermission('update_profile'),
    ];
}

function requireCustomerAuth(): void
{
    requireLogin();
    if (!userHasAnyPermission(['view_menu', 'view_orders', 'add_to_cart', 'update_profile', 'place_orders'])) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
        exit();
    }
}