<?php
declare(strict_types=1);

namespace App\Order\Presentation\Http\Controllers;

use App\Order\Application\Usecases\CreateOrderUseCase;
use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\GetUserOrdersUseCase;
use App\Order\Application\Usecases\ReorderItemsUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Application\Usecases\GetStaffDashboardStatsUseCase;

use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Shared\Presentation\Http\Controllers\BaseController;

class OrderController extends BaseController
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private FoodRepositoryInterface $foodRepository;
    private GetAllOrdersUseCase $getAllOrdersUseCase;
    private GetUserOrdersUseCase $getUserOrdersUseCase;
    private CreateOrderUseCase $createOrderUseCase;
    private UpdateOrderStatusUseCase $updateOrderStatusUseCase;
    private ReorderItemsUseCase $reorderItemsUseCase;
    private GetStaffDashboardStatsUseCase $getStaffDashboardStatsUseCase;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository,
        GetAllOrdersUseCase $getAllOrdersUseCase,
        GetUserOrdersUseCase $getUserOrdersUseCase,
        CreateOrderUseCase $createOrderUseCase,
        UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        ReorderItemsUseCase $reorderItemsUseCase,
        GetStaffDashboardStatsUseCase $getStaffDashboardStatsUseCase
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
        $this->getAllOrdersUseCase = $getAllOrdersUseCase;
        $this->getUserOrdersUseCase = $getUserOrdersUseCase;
        $this->createOrderUseCase = $createOrderUseCase;
        $this->updateOrderStatusUseCase = $updateOrderStatusUseCase;
        $this->reorderItemsUseCase = $reorderItemsUseCase;
        $this->getStaffDashboardStatsUseCase = $getStaffDashboardStatsUseCase;
    }

    /**
     * Get all orders - Staff/Admin only
     */
    public function index(): array
    {
        $this->authorizeAny(['manage_orders', 'view_orders']);
        return $this->getAllOrdersUseCase->execute();
    }

    /**
     * Get recent orders - Staff/Admin only
     */
    public function getRecentOrders(int $limit = 10): array
    {
        $this->authorizeAny(['manage_orders', 'view_orders']);
        return $this->getAllOrdersUseCase->getRecentOrders($limit);
    }

    /**
     * Get user orders - Users can view their own, staff/admin can view any
     */
    public function getUserOrders(int $userId): array
    {
        $this->authorizeResource($userId, 'view_orders');
        return $this->getUserOrdersUseCase->execute($userId);
    }

    /**
     * Create order - Customers can place orders
     */
    public function createOrder(
        int $userId,
        array $items,
        float $total,
        string $address,
        string $paymentMethod,
        string $fullName,
        string $phone,
        ?string $accountName = null,
        ?string $accountNumber = null,
        ?string $transactionImage = null
    ): array {
        $this->authorize('place_orders');
        
        $currentUserId = $this->getCurrentUserId();
        if ($userId !== $currentUserId && !$this->isAdmin()) {
            throw new \RuntimeException('Cannot create order for another user', 403);
        }
        
        return $this->createOrderUseCase->execute(
            $userId,
            $items,
            $total,
            $address,
            $paymentMethod,
            $fullName,
            $phone,
            $accountName,
            $accountNumber,
            $transactionImage
        );
    }

    /**
     * Update order status - Staff/Admin only
     */
    public function updateStatus(int $orderId, int $statusId): array
    {
        $this->authorizeAny(['manage_orders', 'update_order_status']);
        return $this->updateOrderStatusUseCase->execute($orderId, $statusId);
    }

    /**
     * Reorder items - Users can reorder their own, staff can reorder any
     */
    public function reorder(int $userId, int $orderId): array
    {
        $this->authorizeResource($userId, 'manage_orders');
        return $this->reorderItemsUseCase->execute($userId, $orderId);
    }

    /**
     * Get order statuses - No permission needed
     */
    public function getStatuses(): array
    {
        return $this->orderRepository->getOrderStatuses();
    }

/**
 * Get order items - Users can view their own, staff/admin can view any
 */
public function getOrderItems(int $orderId): array
{
    // ✅ First, get the order to check ownership
    $order = $this->orderRepository->findById($orderId);
    
    // ✅ Check if order exists
    if (!$order) {
        throw new \RuntimeException('Order not found', 404);
    }
    
    // ✅ Check if user has access to this order
    $userId = $order->getUserId(); // If Order object has getUserId()
    // OR if it's an array: $userId = $order['user_id'] ?? 0;
    
    $this->authorizeResource($userId, 'view_orders');
    
    // ✅ Return the order items (array)
    return $this->orderRepository->getOrderItems($orderId);
}

    /**
     * Get total orders count - Staff/Admin only
     */
    public function getTotalOrders(): int
    {
        $this->authorizeAny(['view_reports', 'manage_orders']);
        return $this->orderRepository->getTotalOrders();
    }

    /**
     * Get pending orders count - Staff/Admin only
     */
    public function getPendingOrders(): int
    {
        $this->authorizeAny(['view_reports', 'manage_orders']);
        return $this->orderRepository->getPendingOrders();
    }

    /**
     * Get single order - Users can view their own, staff/admin can view any
     */
    public function getOrder(int $orderId)
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found', 404);
        }
        
        $this->authorizeResource($order['user_id'] ?? 0, 'view_orders');
        return $order;
    }

    /**
     * Get order statistics - Staff/Admin only
     */
    public function getOrderStats(): array
    {
        $this->authorizeAny(['view_reports', 'manage_orders']);
        return [
            'total' => $this->orderRepository->getTotalOrders(),
            'pending' => $this->orderRepository->getPendingOrders(),
            'statuses' => $this->orderRepository->getOrderStatuses()
        ];
    }

    /**
     * Cancel order - Users can cancel their own pending orders, staff can cancel any
     */
    public function cancelOrder(int $orderId): array
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found', 404);
        }
        
        $this->authorizeResource($order['user_id'] ?? 0, 'update_order_status');
        
        if (($order['status_id'] ?? 0) !== 1) {
            throw new \RuntimeException('Only pending orders can be cancelled', 400);
        }
        
        return $this->updateOrderStatusUseCase->execute($orderId, 6); // 6 = cancelled
    }
     /**
     * Get staff dashboard statistics - Staff/Admin only
     */
    public function getStaffDashboardStats(): array
    {
        $this->authorizeAny(['view_reports', 'manage_orders', 'view_dashboard']);
        return $this->getStaffDashboardStatsUseCase->execute();
    }

    /**
     * Get total orders count - Staff/Admin only
     */
    // public function getTotalOrders(): int
    // {
    //     $this->authorizeAny(['view_reports', 'manage_orders']);
    //     return $this->getStaffDashboardStatsUseCase->getTotalOrders();
    // }

    /**
     * Get pending orders count - Staff/Admin only
     */
    // public function getPendingOrders(): int
    // {
    //     $this->authorizeAny(['view_reports', 'manage_orders']);
    //     return $this->getStaffDashboardStatsUseCase->getPendingOrders();
    // }

    /**
     * Get preparing orders count - Staff/Admin only
     */
    public function getPreparingOrders(): int
    {
        $this->authorizeAny(['view_reports', 'manage_orders']);
        return $this->getStaffDashboardStatsUseCase->getPreparingOrders();
    }

    /**
     * Get completed orders count - Staff/Admin only
     */
    public function getCompletedOrders(): int
    {
        $this->authorizeAny(['view_reports', 'manage_orders']);
        return $this->getStaffDashboardStatsUseCase->getCompletedOrders();
    }

    /**
     * Get recent orders - Staff/Admin only
     */
    // public function getRecentOrders(int $limit = 5): array
    // {
    //     $this->authorizeAny(['view_reports', 'manage_orders', 'view_orders']);
    //     return $this->getStaffDashboardStatsUseCase->getRecentOrders($limit);
    // }

}