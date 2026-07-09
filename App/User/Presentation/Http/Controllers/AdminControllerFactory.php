<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Infrastructure\Repositories\UserRepository;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;

class AdminControllerFactory
{
    private static ?AdminController $instance = null;

    public static function create(): AdminController
    {
        $userRepository = new UserRepository();
        $orderRepository = new OrderRepository();
        $foodRepository = new FoodRepository();
        $userController = UserControllerFactory::create();

        return new AdminController(
            $userRepository,
            $orderRepository,
            $foodRepository,
            $userController
        );
    }

    public static function getInstance(): AdminController
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }
}