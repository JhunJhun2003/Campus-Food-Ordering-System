<?php

use App\Order\Presentation\Http\Controllers\OrderController;
use App\Order\Application\Usecases\CreateOrderUseCase;
use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\GetUserOrdersUseCase;
use App\Order\Application\Usecases\ReorderItemsUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Application\Usecases\GetStaffDashboardStatsUseCase;
use App\Payment\Application\Services\PaymentService;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Cart\Infrastructure\Repositories\CartRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;
use App\Cart\Presentation\Http\Controllers\CartControllerFactory;
use App\Food\Presentation\Http\Controllers\FoodControllerFactory;
use App\Payment\Presentation\Http\Controllers\PaymentControllerFactory;
use App\Payment\Infrastructure\Repositories\PaymentRepository;
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
        $paymentRepository = new PaymentRepository();

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
$getStaffDashboardStatsUseCase = new GetStaffDashboardStatsUseCase($orderRepository);
$paymentService = new PaymentService($paymentRepository);
        $instance = new OrderController(
            $orderRepository,
            $cartRepository,
            $foodRepository,
            $getAllOrdersUseCase,
            $getUserOrdersUseCase,
            $createOrderUseCase,
            $updateOrderStatusUseCase,
            $reorderItemsUseCase,
            $getStaffDashboardStatsUseCase  ,
            $paymentService 
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
        $instance = CartControllerFactory::getInstance();
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
        $instance = FoodControllerFactory::getInstance();
    }
    
    return $instance;
}


/**
 * Get Payment Controller with all dependencies injected
 */
function getPaymentController(): \App\Payment\Presentation\Http\Controllers\PaymentController
{
    static $instance = null;
    
    if ($instance === null) {
        $instance = PaymentControllerFactory::getInstance();
    }
    
    return $instance;
}