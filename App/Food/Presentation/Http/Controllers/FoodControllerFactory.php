<?php
declare(strict_types=1);

namespace App\Food\Presentation\Http\Controllers;

use App\Food\Infrastructure\Repositories\FoodRepository;
use App\Food\Infrastructure\Repositories\CategoryRepository;

/**
 * Food Controller Factory
 * Creates FoodController with all dependencies
 * Follows Factory Pattern and Dependency Inversion Principle
 */
class FoodControllerFactory
{
    private static ?FoodController $instance = null;

    /**
     * Create FoodController with all dependencies
     */
    public static function create(): FoodController
    {
        $foodRepository = new FoodRepository();
        $categoryRepository = new CategoryRepository();

        return new FoodController(
            $foodRepository,
            $categoryRepository
        );
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): FoodController
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
        FoodRepository $foodRepository,
        CategoryRepository $categoryRepository
    ): FoodController {
        return new FoodController(
            $foodRepository,
            $categoryRepository
        );
    }
}