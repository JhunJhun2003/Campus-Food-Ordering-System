<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
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
                $currentStock = $this->foodRepository->getStock($foodId);
                
                if ($currentStock < $quantity) {
                    $food = $this->foodRepository->findById($foodId);
                    $foodName = $food ? $food->getName() : "Item #$foodId";
                    throw new \Exception("Not enough stock for '$foodName'. Available: $currentStock");
                }
            }

            // 3. REDUCE STOCK
            foreach ($items as $item) {
                $this->foodRepository->reduceStock($item['food_id'], $item['quantity']);
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
                    $item['price']
                );
            }

            // 7. Clear cart
            $this->cartRepository->clear($userId);

            // ✅ All operations succeeded, commit transaction
            $db->commit();

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