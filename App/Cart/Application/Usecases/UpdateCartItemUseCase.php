<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;

class UpdateCartItemUseCase
{
    private CartRepositoryInterface $cartRepository;
    private FoodRepositoryInterface $foodRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
    }

    public function execute(int $userId, int $cartItemId, int $quantity): array
    {
        if ($quantity <= 0) {
            $this->cartRepository->removeItem($userId, $cartItemId);
            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'item_count' => $this->cartRepository->getItemCount($userId),
                'total' => $this->cartRepository->getTotal($userId)
            ];
        }

        // Retrieve current cart to find the item and its food_size_id
        $cart = $this->cartRepository->findByUserId($userId);
        if (!$cart) {
            return ['success' => false, 'message' => 'Cart not found'];
        }

        $targetItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $cartItemId) {
                $targetItem = $item;
                break;
            }
        }

        if (!$targetItem) {
            return ['success' => false, 'message' => 'Cart item not found'];
        }

        $sizeId = $targetItem->getFoodSizeId();
        if ($sizeId !== null) {
            $size = $this->foodRepository->findSizeById($sizeId);
            if ($size && $size->getStock() < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Cannot update quantity. Only ' . $size->getStock() . ' items are in stock.'
                ];
            }
        }

        $this->cartRepository->updateItemQuantity($userId, $cartItemId, $quantity);
        
        return [
            'success' => true,
            'message' => 'Cart updated successfully',
            'item_count' => $this->cartRepository->getItemCount($userId),
            'total' => $this->cartRepository->getTotal($userId)
        ];
    }
}