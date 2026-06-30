<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Repositories\OrderRepositoryInterface;

class GetAllOrdersUseCase
{
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(): array
    {
        return $this->orderRepository->findAll();
    }

    public function getRecentOrders(int $limit = 10): array
    {
        return $this->orderRepository->getRecentOrders($limit);
    }
}