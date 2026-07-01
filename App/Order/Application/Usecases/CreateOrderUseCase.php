<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;

class CreateOrderUseCase
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
    }

    public function execute(int $userId, array $items, float $total, string $address, string $paymentMethod): array
    {
        try {
            // status_id = 1 means 'pending'
            $statusId = 1;

            // Create order
            $order = new Order(
                null,
                $userId,
                $statusId,
                $total,
                $address,
                $paymentMethod
            );

            $orderId = $this->orderRepository->save($order);

            // Save order items
            foreach ($items as $item) {
                $this->orderRepository->addItem(
                    $orderId,
                    $item['food_id'],
                    $item['quantity'],
                    $item['price']
                );
            }

            // Clear cart
            $this->cartRepository->clear($userId);

            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Order placed successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}