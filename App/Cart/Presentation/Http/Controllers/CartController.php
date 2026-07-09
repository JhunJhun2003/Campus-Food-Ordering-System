<?php
declare(strict_types=1);

namespace App\Cart\Presentation\Http\Controllers;

use App\Cart\Application\Usecases\GetCartUseCase;
use App\Cart\Application\Usecases\AddToCartUseCase;
use App\Cart\Application\Usecases\UpdateCartItemUseCase;
use App\Cart\Application\Usecases\RemoveFromCartUseCase;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;

/**
 * Cart Controller
 * Follows SOLID principles with Dependency Injection
 * No 'new' keyword - all dependencies are injected
 */
class CartController
{
    private CartRepositoryInterface $cartRepository;
    private FoodRepositoryInterface $foodRepository;

    /**
     * Constructor with Dependency Injection
     * All dependencies are injected, not created inside
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
    }

    /**
     * Get cart contents
     */
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

    /**
     * Update cart item quantity
     */
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

    /**
     * Remove item from cart
     */
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

    /**
     * Clear all items from cart
     */
    public function clear(int $userId): array
    {
        $this->cartRepository->clear($userId);
        return [
            'success' => true,
            'message' => 'Cart cleared successfully',
            'item_count' => 0
        ];
    }

    /**
     * Get total item count in cart
     */
    public function getItemCount(int $userId): int
    {
        return $this->cartRepository->getItemCount($userId);
    }
}