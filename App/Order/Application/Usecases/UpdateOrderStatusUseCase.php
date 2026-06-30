<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Repositories\OrderRepositoryInterface;

class UpdateOrderStatusUseCase
{
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(int $orderId, int $statusId): array
    {
        try {
            $this->orderRepository->updateStatus($orderId, $statusId);
            return [
                'success' => true,
                'message' => 'Order status updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ];
        }
    }
}