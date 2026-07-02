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

    /**
     * Add item to cart with item count in response
     */
    public function add(int $userId, int $foodId, int $quantity = 1): array
    {
        $useCase = new AddToCartUseCase($this->cartRepository, $this->foodRepository);
        $result = $useCase->execute($userId, $foodId, $quantity);
        
        // Add item count to response for frontend badge update
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    public function update(int $userId, int $foodId, int $quantity): array
    {
        $useCase = new UpdateCartItemUseCase($this->cartRepository);
        $result = $useCase->execute($userId, $foodId, $quantity);
        
        // Add item count to response
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    public function remove(int $userId, int $foodId): array
    {
        $useCase = new RemoveFromCartUseCase($this->cartRepository);
        $result = $useCase->execute($userId, $foodId);
        
        // Add item count to response
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    public function clear(int $userId): array
    {
        $this->cartRepository->clear($userId);
        return [
            'success' => true,
            'message' => 'Cart cleared successfully',
            'item_count' => 0
        ];
    }

    public function getItemCount(int $userId): int
    {
        return $this->cartRepository->getItemCount($userId);
    }
}