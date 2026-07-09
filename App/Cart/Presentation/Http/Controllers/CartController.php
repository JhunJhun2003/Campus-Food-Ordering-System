<?php
declare(strict_types=1);

namespace App\Cart\Presentation\Http\Controllers;

use App\Cart\Application\Usecases\GetCartUseCase;
use App\Cart\Application\Usecases\AddToCartUseCase;
use App\Cart\Application\Usecases\UpdateCartItemUseCase;
use App\Cart\Application\Usecases\RemoveFromCartUseCase;
use App\Cart\Domain\Repositories\CartRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Shared\Presentation\Http\Controllers\BaseController;

class CartController extends BaseController
{
    private CartRepositoryInterface $cartRepository;
    private FoodRepositoryInterface $foodRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        FoodRepositoryInterface $foodRepository
    ) {
        parent::__construct();
        $this->cartRepository = $cartRepository;
        $this->foodRepository = $foodRepository;
    }

    /**
     * Get cart contents - Users can only view their own cart
     */
    public function index(int $userId): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        $useCase = new GetCartUseCase($this->cartRepository);
        return $useCase->execute($userId);
    }

    /**
     * Add item to cart - Users can only add to their own cart
     */
    public function add(int $userId, int $foodId, int $quantity = 1): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        $this->authorize('add_to_cart');
        
        $useCase = new AddToCartUseCase($this->cartRepository, $this->foodRepository);
        $result = $useCase->execute($userId, $foodId, $quantity);
        
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    /**
     * Update cart item quantity - Users can only update their own cart
     */
    public function update(int $userId, int $foodId, int $quantity): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        $useCase = new UpdateCartItemUseCase($this->cartRepository);
        $result = $useCase->execute($userId, $foodId, $quantity);
        
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    /**
     * Remove item from cart - Users can only remove from their own cart
     */
    public function remove(int $userId, int $foodId): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        $useCase = new RemoveFromCartUseCase($this->cartRepository);
        $result = $useCase->execute($userId, $foodId);
        
        if ($result['success']) {
            $result['item_count'] = $this->cartRepository->getItemCount($userId);
        }
        
        return $result;
    }

    /**
     * Clear all items from cart - Users can only clear their own cart
     */
    public function clear(int $userId): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        $this->cartRepository->clear($userId);
        return [
            'success' => true,
            'message' => 'Cart cleared successfully',
            'item_count' => 0
        ];
    }

    /**
     * Get total item count in cart - Users can only view their own cart count
     */
    public function getItemCount(int $userId): int
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        return $this->cartRepository->getItemCount($userId);
    }
}