<?php

use App\User\Presentation\Http\Controllers\AdminControllerFactory;

require_once __DIR__ . '/notification_helpers.php';

/**
 * Get Admin Controller with all dependencies injected
 */
function getAdminController(): \App\User\Presentation\Http\Controllers\AdminController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = AdminControllerFactory::getInstance();
    }
    
    return $instance;
}

