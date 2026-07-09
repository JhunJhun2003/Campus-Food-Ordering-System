<?php

use App\User\Presentation\Http\Controllers\UserControllerFactory;
use App\User\Presentation\Http\Controllers\AdminControllerFactory;

function getUserController(): \App\User\Presentation\Http\Controllers\UserController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = UserControllerFactory::getInstance();
    }
    
    return $instance;
}



