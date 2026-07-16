<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Entities\CartItem;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Infrastructure\Repositories\FoodRepository;
use App\Food\Domain\Entities\FoodSize;

class AddToCartUseCase
{
    private CartRepositoryInterface $cartRepository;
    private FoodRepository $foodRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        FoodRepository $foodRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
    }

    /**
     * Add item to cart using food_id (uses default size)
     */
    public function execute(int $userId, int $foodId, int $quantity = 1): array
    {
        return $this->executeWithSize($userId, $foodId, null, $quantity);
    }

    /**
     * Add item to cart using food_size_id (preferred method)
     */
    public function executeWithSize(int $userId, int $foodId, ?int $foodSizeId = null, int $quantity = 1): array
    {
        $food = $this->foodRepository->findById($foodId);
        
        if (!$food) {
            return ['success' => false, 'message' => 'Food item not found'];
        }

        // Get sizes
        $sizes = $this->foodRepository->getSizes($foodId);
        $food->setSizes($sizes);

        // Determine which size to use
        $size = null;
        
        if ($foodSizeId !== null) {
            // Use specified size
            $size = $food->getSizeById($foodSizeId);
            if (!$size) {
                return ['success' => false, 'message' => 'Selected size not found for this food item'];
            }
        } else {
            // Use default size
            $size = $food->getDefaultSize();
            if (!$size) {
                return ['success' => false, 'message' => 'No default size available for this food item'];
            }
        }

        // Check stock for the specific size
        if ($size->getStock() < $quantity) {
            return ['success' => false, 'message' => 'Not enough stock available for ' . $size->getSizeName() . ' size'];
        }

        // Create cart item with size
        $cartItem = new CartItem(
            null,
            $foodId,
            $food->getName(),
            $size->getPrice(),
            $quantity,
            $food->getImage(),
            $size->getId(),
            $size->getSizeName()
        );

        $this->cartRepository->addItem($userId, $cartItem);

        return [
            'success' => true,
            'message' => 'Item added to cart successfully',
            'item_count' => $this->cartRepository->getItemCount($userId),
            'size' => $size->getSizeName(),
            'price' => $size->getPrice()
        ];
    }

    /**
     * Add multiple items with different sizes
     */
    public function executeMultiple(int $userId, array $items): array
    {
        $results = [];
        $errors = [];

        foreach ($items as $item) {
            $foodId = $item['food_id'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $foodSizeId = $item['food_size_id'] ?? null;

            $result = $this->executeWithSize($userId, $foodId, $foodSizeId, $quantity);
            
            if ($result['success']) {
                $results[] = $result;
            } else {
                $errors[] = $result;
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Some items could not be added to cart',
                'errors' => $errors,
                'results' => $results
            ];
        }

        return [
            'success' => true,
            'message' => 'All items added to cart successfully',
            'results' => $results,
            'item_count' => $this->cartRepository->getItemCount($userId)
        ];
    }
}