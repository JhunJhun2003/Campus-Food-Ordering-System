<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Notification\Application\Services\NotificationDispatcher;
use Inc\Database;

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
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Multiple operations must succeed together
            $db->beginTransaction();
            
            // 1. Validate
            if (empty($items)) {
                throw new \Exception('Cart is empty.');
            }

            // 2. CHECK STOCK
            foreach ($items as $item) {
                $foodId = $item['food_id'];
                $quantity = $item['quantity'];
                $foodSizeId = isset($item['food_size_id']) ? (int) $item['food_size_id'] : null;
                
                if ($foodSizeId !== null) {
                    $size = $this->foodRepository->findSizeById($foodSizeId);
                    if (!$size) {
                        $food = $this->foodRepository->findById($foodId);
                        $foodName = $food ? $food->getName() : "Item #$foodId";
                        throw new \Exception("Selected size for '$foodName' is not available.");
                    }
                    if ($size->getStock() < $quantity) {
                        throw new \Exception("Not enough stock for '{$size->getSizeName()}'. Available: {$size->getStock()}");
                    }
                } else {
                    $currentStock = $this->foodRepository->getStock($foodId);
                    if ($currentStock < $quantity) {
                        $food = $this->foodRepository->findById($foodId);
                        $foodName = $food ? $food->getName() : "Item #$foodId";
                        throw new \Exception("Not enough stock for '$foodName'. Available: $currentStock");
                    }
                }
            }

            // 3. REDUCE STOCK
            foreach ($items as $item) {
                $foodSizeId = isset($item['food_size_id']) ? (int) $item['food_size_id'] : null;
                if ($foodSizeId !== null) {
                    if (!$this->foodRepository->reduceSizeStock($foodSizeId, $item['quantity'])) {
                        throw new \Exception('Unable to reserve stock for selected size.');
                    }
                } else {
                    if (!$this->foodRepository->reduceStock($item['food_id'], $item['quantity'])) {
                        throw new \Exception('Unable to reserve stock for selected item.');
                    }
                }
            }

            // 4. Create order (status_id = 1 means 'pending')
            $order = new Order(
                null,
                $userId,
                1,
                $total,
                $address,
                $paymentMethod,
                $fullName,
                $phone,
                $accountName,
                $accountNumber,
                $transactionImage
            );

            // 5. Save order
            $orderId = $this->orderRepository->save($order);

            // 6. Save order items
            foreach ($items as $item) {
                $this->orderRepository->addItem(
                    $orderId,
                    $item['food_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['food_size_id'] ?? null
                );
            }

            // 7. Clear cart
            $this->cartRepository->clear($userId);

            // ✅ All operations succeeded, commit transaction
            $db->commit();

            NotificationDispatcher::orderStatus($userId, $orderId, 1);
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Order placed successfully'
            ];

        } catch (\Exception $e) {
            // ✅ Rollback all changes on any error
            $db->rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}