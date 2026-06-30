<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Repositories\CartRepositoryInterface;

class RemoveFromCartUseCase
{
    private CartRepositoryInterface $cartRepository;

    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function execute(int $userId, int $foodId): array
    {
        $this->cartRepository->removeItem($userId, $foodId);
        
        return [
            'success' => true,
            'message' => 'Item removed from cart',
            'item_count' => $this->cartRepository->getItemCount($userId),
            'total' => $this->cartRepository->getTotal($userId)
        ];
    }
}