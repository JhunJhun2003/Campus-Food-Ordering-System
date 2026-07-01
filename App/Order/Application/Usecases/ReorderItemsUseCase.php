<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Cart\Domain\Entities\CartItem;

class ReorderItemsUseCase
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

    public function execute(int $userId, int $orderId): array
    {
        try {
            // Get order items
            $items = $this->orderRepository->getOrderItems($orderId);
            
            if (empty($items)) {
                return ['success' => false, 'message' => 'No items found in this order'];
            }

            // Add items to cart
            foreach ($items as $item) {
                $cartItem = new CartItem(
                    null,
                    $item['food_id'],
                    $item['food_name'],
                    $item['unit_price'],
                    $item['quantity'],
                    null
                );
                $this->cartRepository->addItem($userId, $cartItem);
            }

            return [
                'success' => true,
                'message' => 'Items added to cart successfully',
                'item_count' => $this->cartRepository->getItemCount($userId)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}