<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Repositories\CartRepositoryInterface;

class UpdateCartItemUseCase
{
    private CartRepositoryInterface $cartRepository;

    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function execute(int $userId, int $cartItemId, int $quantity): array
    {
        if ($quantity <= 0) {
            $this->cartRepository->removeItem($userId, $cartItemId);
            return ['success' => true, 'message' => 'Item removed from cart'];
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