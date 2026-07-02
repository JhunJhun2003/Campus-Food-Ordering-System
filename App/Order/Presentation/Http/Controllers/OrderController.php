<?php
namespace App\Order\Presentation\Http\Controllers;

use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\GetUserOrdersUseCase;
use App\Order\Application\Usecases\CreateOrderUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Application\Usecases\ReorderItemsUseCase;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Cart\Infrastructure\Repositories\CartRepository;
use Inc\Database;

class OrderController
{
    private OrderRepository $orderRepository;
    private CartRepository $cartRepository;

    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->cartRepository = new CartRepository();
    }

    /**
     * Get all orders (admin)
     */
    public function index(): array
    {
        $useCase = new GetAllOrdersUseCase($this->orderRepository);
        return $useCase->execute();
    }

    /**
     * Get recent orders (admin dashboard)
     */
    public function getRecentOrders(int $limit = 10): array
    {
        $useCase = new GetAllOrdersUseCase($this->orderRepository);
        return $useCase->getRecentOrders($limit);
    }

    /**
     * Get orders for a specific user (customer orders page)
     */
    public function getUserOrders(int $userId): array
    {
        $useCase = new GetUserOrdersUseCase($this->orderRepository);
        return $useCase->execute($userId);
    }

    /**
     * Create a new order (checkout) - UPDATED with all fields
     */
    public function createOrder(
        int $userId, 
        array $items, 
        float $total, 
        string $address, 
        string $paymentMethod, 
        string $fullName, 
        string $phone, 
        string $accountName, 
        string $accountNumber, 
        string $transactionImage
    ): array {
        $useCase = new CreateOrderUseCase($this->orderRepository, $this->cartRepository);
        return $useCase->execute(
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
     * Update order status (admin/staff)
     */
    public function updateStatus(int $orderId, int $statusId): array
    {
        $useCase = new UpdateOrderStatusUseCase($this->orderRepository);
        return $useCase->execute($orderId, $statusId);
    }

    /**
     * Reorder items from a previous order (customer)
     */
    public function reorder(int $userId, int $orderId): array
    {
        $useCase = new ReorderItemsUseCase($this->orderRepository, $this->cartRepository);
        return $useCase->execute($userId, $orderId);
    }

    /**
     * Get all order statuses
     */
    public function getStatuses(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM order_statuses ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total orders count (admin dashboard)
     */
    public function getTotalOrders(): int
    {
        return $this->orderRepository->getTotalOrders();
    }

    /**
     * Get pending orders count (admin dashboard)
     */
    public function getPendingOrders(): int
    {
        return $this->orderRepository->getPendingOrders();
    }
}