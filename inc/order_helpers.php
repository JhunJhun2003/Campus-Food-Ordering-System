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
use App\Cart\Presentation\Http\Controllers\CartController;

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

function getCartController(): CartController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = new CartController();
    }
    
    return $instance;
}