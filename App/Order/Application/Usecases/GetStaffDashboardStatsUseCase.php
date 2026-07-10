<?php
declare(strict_types=1);

namespace App\Order\Application\Usecases;

use App\Order\Domain\Repositories\OrderRepositoryInterface;

class GetStaffDashboardStatsUseCase
{
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute the use case - Get all dashboard statistics
     * 
     * @return array{
     *     totalOrders: int,
     *     pendingOrders: int,
     *     preparingOrders: int,
     *     completedOrders: int,
     *     recentOrders: array
     * }
     */
    public function execute(): array
    {
        return [
            'totalOrders' => $this->orderRepository->getTotalOrders(),
            'pendingOrders' => $this->orderRepository->getPendingOrders(),
            'preparingOrders' => $this->orderRepository->getPreparingOrders(),
            'completedOrders' => $this->orderRepository->getCompletedOrders(),
            'recentOrders' => $this->orderRepository->getRecentOrders(5),
        ];
    }

    /**
     * Get only order statistics (without recent orders)
     */
    public function getStats(): array
    {
        return [
            'totalOrders' => $this->orderRepository->getTotalOrders(),
            'pendingOrders' => $this->orderRepository->getPendingOrders(),
            'preparingOrders' => $this->orderRepository->getPreparingOrders(),
            'completedOrders' => $this->orderRepository->getCompletedOrders(),
        ];
    }

    /**
     * Get total orders count
     */
    public function getTotalOrders(): int
    {
        return $this->orderRepository->getTotalOrders();
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrders(): int
    {
        return $this->orderRepository->getPendingOrders();
    }

    /**
     * Get preparing orders count
     */
    public function getPreparingOrders(): int
    {
        return $this->orderRepository->getPreparingOrders();
    }

    /**
     * Get completed orders count
     */
    public function getCompletedOrders(): int
    {
        return $this->orderRepository->getCompletedOrders();
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return $this->orderRepository->getRecentOrders($limit);
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus(int $statusId): array
    {
        return $this->orderRepository->findByStatus($statusId);
    }

    /**
     * Get today's orders count
     */
    public function getTodaysOrders(): int
    {
        return $this->orderRepository->countToday();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(): float
    {
        return $this->orderRepository->getTotalRevenue();
    }
}