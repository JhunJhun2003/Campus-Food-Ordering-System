<?php
declare(strict_types=1);

namespace App\Cart\Presentation\Http\Controllers;

use App\Cart\Infrastructure\Repositories\CartRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;

/**
 * Cart Controller Factory
 * Creates CartController with all dependencies
 * Follows Factory Pattern and Dependency Inversion Principle
 */
class CartControllerFactory
{
    private static ?CartController $instance = null;

    /**
     * Create CartController with all dependencies
     */
    public static function create(): CartController
    {
        $cartRepository = new CartRepository();
        $foodRepository = new FoodRepository();

        return new CartController(
            $cartRepository,
            $foodRepository
        );
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): CartController
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    /**
     * For testing - allows injecting mocks
     */
    public static function createWithDependencies(
        CartRepository $cartRepository,
        FoodRepository $foodRepository
    ): CartController {
        return new CartController(
            $cartRepository,
            $foodRepository
        );
    }
}