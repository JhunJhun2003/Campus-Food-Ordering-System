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
use App\Payment\Application\Services\PaymentService;
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
    private PaymentService $paymentService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository,
        GetAllOrdersUseCase $getAllOrdersUseCase,
        GetUserOrdersUseCase $getUserOrdersUseCase,
        CreateOrderUseCase $createOrderUseCase,
        UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        ReorderItemsUseCase $reorderItemsUseCase,
        GetStaffDashboardStatsUseCase $getStaffDashboardStatsUseCase,
        PaymentService $paymentService
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
        $this->paymentService = $paymentService;
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
     * Uses PaymentService to handle payment creation
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
        
        // Ensure all values are strings
        $accountName = $accountName ?? '';
        $accountNumber = $accountNumber ?? '';
        $transactionImage = $transactionImage ?? '';
        
        // Create the order
        $result = $this->createOrderUseCase->execute(
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
        
        // If order created successfully, create payment record using PaymentService
        if ($result['success'] && isset($result['order_id'])) {
            $this->paymentService->createPaymentForOrder(
                $result['order_id'],
                $paymentMethod,
                $total,
                $transactionImage
            );
        }
        
        return $result;
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
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found', 404);
        }
        
        $userId = $order->getUserId();
        $this->authorizeResource($userId, 'view_orders');
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
     * Get single order - Users can view their own, staff/admin can view any
     */
    public function getOrder(int $orderId): object
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found', 404);
        }
        
        $this->authorizeResource($order->getUserId(), 'view_orders');
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
        
        $this->authorizeResource($order->getUserId(), 'update_order_status');
        
        if ($order->getStatusId() !== 1) {
            throw new \RuntimeException('Only pending orders can be cancelled', 400);
        }
        
        return $this->updateOrderStatusUseCase->execute($orderId, 6);
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
     * Get order with payment details for staff view
     * Uses PaymentService to get payment information
     */
    public function getOrderWithDetails(int $orderId): ?array
    {
        $this->authorizeAny(['manage_orders', 'view_orders']);
        
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found', 404);
        }
        
        // Get payment details using PaymentService
        $payment = $this->paymentService->getPaymentByOrderId($orderId);
        
        // If no payment record, assume COD
        if (!$payment) {
            $payment = [
                'payment_method_name' => 'Cash on Delivery',
                'payment_account_name' => null,
                'payment_account_number' => null,
                'payment_status_name' => 'pending',
                'payment_amount' => $order->getTotalAmount(),
                'transaction_no' => null,
                'transaction_image' => null
            ];
        }
        
        return [
            'order' => $order,
            'payment' => $payment,
            'items' => $this->orderRepository->getOrderItems($orderId)
        ];
    }
}