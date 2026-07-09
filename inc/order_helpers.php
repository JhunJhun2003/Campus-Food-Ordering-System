<?php

use App\Order\Presentation\Http\Controllers\OrderController;
use App\Order\Application\Usecases\CreateOrderUseCase;
use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\GetUserOrdersUseCase;
use App\Order\Application\Usecases\ReorderItemsUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Cart\Infrastructure\Repositories\CartRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;
use App\Cart\Presentation\Http\Controllers\CartControllerFactory;

/**
 * Get Order Controller with all dependencies injected
 */
function getOrderController(): OrderController
{
    static $instance = null;
    
    if ($instance === null) {
        // Repositories
        $orderRepository = new OrderRepository();
        $cartRepository = new CartRepository();
        $foodRepository = new FoodRepository();

        // Use Cases
        $getAllOrdersUseCase = new GetAllOrdersUseCase($orderRepository);
        $getUserOrdersUseCase = new GetUserOrdersUseCase($orderRepository);
        $createOrderUseCase = new CreateOrderUseCase(
            $orderRepository,
            $cartRepository,
            $foodRepository
        );
        $updateOrderStatusUseCase = new UpdateOrderStatusUseCase($orderRepository);
        $reorderItemsUseCase = new ReorderItemsUseCase(
            $orderRepository,
            $cartRepository
        );

        $instance = new OrderController(
            $orderRepository,
            $cartRepository,
            $foodRepository,
            $getAllOrdersUseCase,
            $getUserOrdersUseCase,
            $createOrderUseCase,
            $updateOrderStatusUseCase,
            $reorderItemsUseCase
        );
    }
    
    return $instance;
}

/**
 * Get Cart Controller with all dependencies injected
 */
function getCartController(): \App\Cart\Presentation\Http\Controllers\CartController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = \App\Cart\Presentation\Http\Controllers\CartControllerFactory::getInstance();
    }
    
    return $instance;
}

/**
 * Get Food Controller with all dependencies injected
 */
function getFoodController(): \App\Food\Presentation\Http\Controllers\FoodController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = \App\Food\Presentation\Http\Controllers\FoodControllerFactory::getInstance();
    }
    
    return $instance;
}