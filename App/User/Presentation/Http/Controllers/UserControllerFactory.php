<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Infrastructure\Repositories\UserRepository;

/**
 * User Controller Factory
 * Creates UserController with all dependencies
 * Follows Factory Pattern and Dependency Inversion Principle
 */
class UserControllerFactory
{
    private static ?UserController $instance = null;

    /**
     * Create UserController with all dependencies
     */
    public static function create(): UserController
    {
        $userRepository = new UserRepository();

        return new UserController(
            $userRepository
        );
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): UserController
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
        UserRepository $userRepository
    ): UserController {
        return new UserController(
            $userRepository
        );
    }
}