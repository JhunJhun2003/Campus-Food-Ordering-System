<?php
namespace App\Cart\Presentation\Http\Controllers;

use App\Cart\Application\Usecases\GetCartUseCase;
use App\Cart\Application\Usecases\AddToCartUseCase;
use App\Cart\Application\Usecases\UpdateCartItemUseCase;
use App\Cart\Application\Usecases\RemoveFromCartUseCase;
use App\Cart\Infrastructure\Repositories\CartRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;

class CartController
{
    private CartRepository $cartRepository;
    private FoodRepository $foodRepository;

    public function __construct()
    {
        $this->cartRepository = new CartRepository();
        $this->foodRepository = new FoodRepository();
    }

    public function index(int $userId): array
    {
        $useCase = new GetCartUseCase($this->cartRepository);
        return $useCase->execute($userId);
    }

    public function add(int $userId, int $foodId, int $quantity = 1): array
    {
        $useCase = new AddToCartUseCase($this->cartRepository, $this->foodRepository);
        return $useCase->execute($userId, $foodId, $quantity);
    }

    public function update(int $userId, int $foodId, int $quantity): array
    {
        $useCase = new UpdateCartItemUseCase($this->cartRepository);
        return $useCase->execute($userId, $foodId, $quantity);
    }

    public function remove(int $userId, int $foodId): array
    {
        $useCase = new RemoveFromCartUseCase($this->cartRepository);
        return $useCase->execute($userId, $foodId);
    }

    public function clear(int $userId): array
    {
        $this->cartRepository->clear($userId);
        return [
            'success' => true,
            'message' => 'Cart cleared successfully'
        ];
    }

    public function getItemCount(int $userId): int
    {
        return $this->cartRepository->getItemCount($userId);
    }
}