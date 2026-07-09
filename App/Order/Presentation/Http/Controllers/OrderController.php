<?php

namespace App\Order\Presentation\Http\Controllers;

use App\Order\Application\Usecases\CreateOrderUseCase;
use App\Order\Application\Usecases\GetAllOrdersUseCase;
use App\Order\Application\Usecases\GetUserOrdersUseCase;
use App\Order\Application\Usecases\ReorderItemsUseCase;
use App\Order\Application\Usecases\UpdateOrderStatusUseCase;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;

class OrderController
{
    private OrderRepositoryInterface $orderRepository;
    private GetAllOrdersUseCase $getAllOrdersUseCase;
    private GetUserOrdersUseCase $getUserOrdersUseCase;
    private CreateOrderUseCase $createOrderUseCase;
    private UpdateOrderStatusUseCase $updateOrderStatusUseCase;
    private ReorderItemsUseCase $reorderItemsUseCase;

    /**
     * ✅ Constructor Injection - All dependencies are injected
     * No 'new' keyword here!
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository,
        GetAllOrdersUseCase $getAllOrdersUseCase,
        GetUserOrdersUseCase $getUserOrdersUseCase,
        CreateOrderUseCase $createOrderUseCase,
        UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        ReorderItemsUseCase $reorderItemsUseCase
    ) {
        $this->orderRepository = $orderRepository;
        $this->getAllOrdersUseCase = $getAllOrdersUseCase;
        $this->getUserOrdersUseCase = $getUserOrdersUseCase;
        $this->createOrderUseCase = $createOrderUseCase;
        $this->updateOrderStatusUseCase = $updateOrderStatusUseCase;
        $this->reorderItemsUseCase = $reorderItemsUseCase;
    }

    public function index(): array
    {
        return $this->getAllOrdersUseCase->execute();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        return $this->getAllOrdersUseCase->getRecentOrders($limit);
    }

    public function getUserOrders(int $userId): array
    {
        return $this->getUserOrdersUseCase->execute($userId);
    }

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

    public function updateStatus(int $orderId, int $statusId): array
    {
        return $this->updateOrderStatusUseCase->execute($orderId, $statusId);
    }

    public function reorder(int $userId, int $orderId): array
    {
        return $this->reorderItemsUseCase->execute($userId, $orderId);
    }

    public function getStatuses(): array
    {
        return $this->orderRepository->getOrderStatuses();
    }

    public function getOrderItems(int $orderId): array
    {
        return $this->orderRepository->getOrderItems($orderId);
    }

    public function getTotalOrders(): int
    {
        return $this->orderRepository->getTotalOrders();
    }

    public function getPendingOrders(): int
    {
        return $this->orderRepository->getPendingOrders();
    }

    public function getOrder(int $orderId)
    {
        return $this->orderRepository->findById($orderId);
    }
}