<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Entities\CartItem;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Infrastructure\Repositories\FoodRepository;

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

    public function execute(int $userId, int $foodId, int $quantity = 1): array
    {
        $food = $this->foodRepository->findById($foodId);
        
        if (!$food) {
            return ['success' => false, 'message' => 'Food item not found'];
        }

        if ($food->getStock() < $quantity) {
            return ['success' => false, 'message' => 'Not enough stock available'];
        }

        $cartItem = new CartItem(
            null,
            $foodId,
            $food->getName(),
            $food->getPrice(),
            $quantity,
            $food->getImage()
        );

        $this->cartRepository->addItem($userId, $cartItem);

        return [
            'success' => true,
            'message' => 'Item added to cart successfully',
            'item_count' => $this->cartRepository->getItemCount($userId)
        ];
    }
}