<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Infrastructure\Repositories\UserRepository;
use App\User\Application\Usecases\LoginWithGoogleUseCase;
use App\User\Infrastructure\Services\GoogleAuthService;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Application\Usecases\GetProfileUseCase;
use App\User\Application\Usecases\UpdateProfileUseCase;
use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Application\Usecases\VerifyEmailUseCase;

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
        // ✅ Repositories
        $userRepository = new UserRepository();

        // ✅ Services
        $googleAuthService = new GoogleAuthService();

        // ✅ Use Cases
        $loginWithGoogleUseCase = new LoginWithGoogleUseCase(
            $googleAuthService,
            $userRepository
        );

        // ✅ RegisterUserUseCase
        $registerUserUseCase = new RegisterUserUseCase($userRepository);

        // ✅ LoginUserUseCase
        $loginUserUseCase = new LoginUserUseCase($userRepository);

        // ✅ GetProfileUseCase
        $getProfileUseCase = new GetProfileUseCase($userRepository);

        // ✅ UpdateProfileUseCase
        $updateProfileUseCase = new UpdateProfileUseCase($userRepository);

        // ✅ SendVerificationUseCase
        $sendVerificationUseCase = new SendVerificationUseCase($userRepository);

        // ✅ VerifyEmailUseCase
        $verifyEmailUseCase = new VerifyEmailUseCase($userRepository);

        // ✅ Create UserController with all dependencies
        return new UserController(
            $userRepository,
            $registerUserUseCase,
            $loginUserUseCase,
            $getProfileUseCase,
            $updateProfileUseCase,
            $sendVerificationUseCase,
            $verifyEmailUseCase,
            $loginWithGoogleUseCase,
            $googleAuthService
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
        UserRepository $userRepository,
        RegisterUserUseCase $registerUserUseCase,
        LoginUserUseCase $loginUserUseCase,
        GetProfileUseCase $getProfileUseCase,
        UpdateProfileUseCase $updateProfileUseCase,
        SendVerificationUseCase $sendVerificationUseCase,
        VerifyEmailUseCase $verifyEmailUseCase,
        LoginWithGoogleUseCase $loginWithGoogleUseCase,
        GoogleAuthService $googleAuthService
    ): UserController {
        return new UserController(
            $userRepository,
            $registerUserUseCase,
            $loginUserUseCase,
            $getProfileUseCase,
            $updateProfileUseCase,
            $sendVerificationUseCase,
            $verifyEmailUseCase,
            $loginWithGoogleUseCase,
            $googleAuthService
        );
    }
}