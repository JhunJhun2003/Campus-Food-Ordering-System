<?php

use App\User\Presentation\Http\Controllers\AdminControllerFactory;

function getAdminController(): \App\User\Presentation\Http\Controllers\AdminController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = AdminControllerFactory::getInstance();
    }
    
    return $instance;
}   