<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;

class CreateOrderUseCase
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private FoodRepositoryInterface $foodRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
    }

    public function execute(
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
        try {
            // Validate
            if (empty($items)) {
                return ['success' => false, 'message' => 'Cart is empty.'];
            }

            // CHECK STOCK
            foreach ($items as $item) {
                $foodId = $item['food_id'];
                $quantity = $item['quantity'];
                $currentStock = $this->foodRepository->getStock($foodId);
                
                if ($currentStock < $quantity) {
                    $food = $this->foodRepository->findById($foodId);
                    $foodName = $food ? $food->getName() : "Item #$foodId";
                    return [
                        'success' => false, 
                        'message' => "Not enough stock for '$foodName'. Available: $currentStock"
                    ];
                }
            }

            // REDUCE STOCK
            foreach ($items as $item) {
                $this->foodRepository->reduceStock($item['food_id'], $item['quantity']);
            }

            // status_id = 1 means 'pending'
            $statusId = 1;

            // Create order entity with all fields
            $order = new Order(
                null,
                $userId,
                $statusId,
                $total,
                $address,
                $paymentMethod,
                $fullName,
                $phone,
                $accountName,
                $accountNumber,
                $transactionImage
            );

            // Save order
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