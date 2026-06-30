<?php
namespace App\Cart\Application\Usecases;

use App\Cart\Domain\Repositories\CartRepositoryInterface;

class GetCartUseCase
{
    private CartRepositoryInterface $cartRepository;

    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function execute(int $userId): array
    {
        $cart = $this->cartRepository->findByUserId($userId);
        
        if (!$cart) {
            return [
                'items' => [],
                'total' => 0,
                'item_count' => 0
            ];
        }

        return $cart->toArray();
    }
}